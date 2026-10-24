<?php

namespace App\Services\Tenant;

use App\Models\Tenant;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Stancl\Tenancy\Database\DatabaseManager;
use Stancl\Tenancy\Jobs\CreateDatabase;
use Throwable;

/**
 * Pool of fully migrated, empty tenant databases.
 *
 * Creating a tenant database means ~50 `CREATE TABLE`s — fsync-bound DDL that takes seconds no matter how the
 * app code is written. The pool does that work ahead of time (`tenants:pool-fill`, scheduled), so provisioning a
 * tenant only claims a ready database with one UPDATE. An empty pool just means the old create-and-migrate path.
 */
class TenantDatabasePool
{
    private const TABLE = 'tenant_database_pool';

    public function enabled(): bool
    {
        return $this->targetSize() > 0;
    }

    public function targetSize(): int
    {
        return max(0, (int) config('tenancy.database_pool_size', 0));
    }

    public function available(): int
    {
        return DB::table(self::TABLE)->whereNull('claimed_by')->count();
    }

    /**
     * Assign a ready database to the tenant. Idempotent (a retried job gets the database it already claimed);
     * returns false when the pool is empty so the caller can fall back to creating one.
     *
     * @return array{name: string, current: bool}|null
     */
    public function claim(Tenant $tenant): ?array
    {
        $claimed = fn () => DB::table(self::TABLE)->where('claimed_by', $tenant->id)->first();

        $row = $claimed();

        if (!$row) {
            // One atomic statement: concurrent workers can never claim the same database.
            DB::table(self::TABLE)
                ->whereNull('claimed_by')
                ->orderBy('id')
                ->limit(1)
                ->update(['claimed_by' => $tenant->id, 'claimed_at' => now(), 'updated_at' => now()]);

            $row = $claimed();
        }

        if (!$row) {
            return null;
        }

        $tenant->setInternal('db_name', $row->database_name);
        Tenant::saveData($tenant->id, ['tenancy_db_name' => $row->database_name]);

        return ['name' => $row->database_name, 'current' => $row->migrated_through === $this->latestMigration()];
    }

    /** Create and migrate databases until `$size` unclaimed ones are ready. Returns how many were created. */
    public function fill(?int $size = null, ?callable $progress = null): int
    {
        $missing = ($size ?? $this->targetSize()) - $this->available();
        $created = 0;

        for ($i = 0; $i < $missing; $i++) {
            $this->createOne();
            $created++;
            $progress && $progress($created, $missing);
        }

        return $created;
    }

    public function latestMigration(): string
    {
        $files = glob(database_path('migrations/tenant/*.php')) ?: [];
        sort($files);

        return $files ? basename(end($files), '.php') : '';
    }

    protected function createOne(): void
    {
        $name = config('tenancy.database.prefix') . 'pool_' . Str::lower(Str::random(12)) . config('tenancy.database.suffix');
        // Never persisted: only carries the database name so the stock tenancy jobs can build and connect to it.
        $scratch = new Tenant(['id' => 'pool-' . Str::uuid()]);
        $scratch->setInternal('db_name', $name);

        try {
            (new CreateDatabase($scratch))->handle(app(DatabaseManager::class));

            tenancy()->initialize($scratch);

            try {
                Artisan::call('migrate', config('tenancy.migration_parameters') + ['--database' => 'tenant']);
            } finally {
                tenancy()->end();
            }
        } catch (Throwable $e) {
            try {
                $scratch->database()->manager()->deleteDatabase($scratch);
            } catch (Throwable) {
                // Nothing was created, or it is already gone.
            }

            throw $e;
        }

        DB::table(self::TABLE)->insert([
            'database_name' => $name,
            'migrated_through' => $this->latestMigration(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
