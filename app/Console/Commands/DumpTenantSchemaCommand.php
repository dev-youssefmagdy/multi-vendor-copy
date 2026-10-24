<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

/**
 * Builds `database/schema/tenant-schema.sql` from a scratch database migrated with the tenant migrations.
 *
 * `tenants:migrate` (and so ProvisionTenantJob) loads this file into an empty tenant database instead of
 * replaying every migration one by one (~10x faster), then runs only the migrations added after the dump.
 * A stale dump is therefore still correct — just slower. Re-run after adding tenant migrations.
 *
 * Unlike `schema:dump`, rows seeded by migrations (e.g. default settings, product badges) are kept.
 */
class DumpTenantSchemaCommand extends Command
{
    protected $signature = 'tenants:schema-dump {--path= : Output file (default database/schema/tenant-schema.sql)}';

    protected $description = 'Dump the tenant database schema (plus migration-seeded rows) used to provision new tenants quickly';

    protected const CONNECTION = 'tenant_schema_dump';

    public function handle(): int
    {
        $path = $this->option('path') ?: database_path('schema/tenant-schema.sql');
        $database = 'tenant_schema_dump_' . now()->format('YmdHis');
        $config = array_merge(config('database.connections.' . config('tenancy.database.central_connection')), [
            'database' => $database,
        ]);

        config(['database.connections.' . self::CONNECTION => $config]);
        DB::statement("CREATE DATABASE `{$database}`");

        try {
            $this->components->task('Migrating scratch database', fn () => Artisan::call('migrate', [
                '--database' => self::CONNECTION,
                '--path' => config('tenancy.migration_parameters.--path'),
                '--realpath' => true,
                '--force' => true,
            ]) === 0);

            if (!is_dir(dirname($path))) {
                mkdir(dirname($path), 0755, true);
            }

            $connection = DB::connection(self::CONNECTION);
            $connection->getSchemaState()->dump($connection, $path);

            $seeded = collect($connection->select('SHOW TABLES'))
                ->map(fn ($row) => array_values((array) $row)[0])
                ->reject(fn (string $table) => $table === 'migrations')
                ->filter(fn (string $table) => $connection->table($table)->exists())
                ->values()
                ->all();

            if ($seeded !== []) {
                file_put_contents($path, $this->dumpRows($config, $seeded), FILE_APPEND);
            }

            $this->components->info("Tenant schema written to {$path}" . ($seeded ? ' (seeded: ' . implode(', ', $seeded) . ')' : ''));
        } finally {
            DB::purge(self::CONNECTION);
            DB::statement("DROP DATABASE IF EXISTS `{$database}`");
        }

        return self::SUCCESS;
    }

    /** @param string[] $tables */
    protected function dumpRows(array $config, array $tables): string
    {
        $process = new Process(array_merge([
            'mysqldump',
            '--host=' . $config['host'],
            '--port=' . $config['port'],
            '--user=' . $config['username'],
            '--no-create-info',
            '--skip-extended-insert',
            '--skip-routines',
            '--skip-triggers',
            '--compact',
            '--complete-insert',
            '--no-tablespaces',
            '--skip-tz-utc',
            $config['database'],
        ], $tables), null, ['MYSQL_PWD' => (string) $config['password']]);

        $process->mustRun();

        return $process->getOutput();
    }
}
