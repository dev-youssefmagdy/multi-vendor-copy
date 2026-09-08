<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\Tenant\TenantCatalogSyncService;
use Illuminate\Console\Command;
use RuntimeException;
use Stancl\Tenancy\Exceptions\TenantDatabaseDoesNotExistException;
use Stancl\Tenancy\Jobs\CreateDatabase;
use Stancl\Tenancy\Jobs\MigrateDatabase;
use Throwable;

class SyncTenantDataCommand extends Command
{
    protected $signature = 'tenants:sync-data
        {tenant? : Tenant id, slug, email, or domain}
        {--all : Sync every tenant}
        {--ensure-database : Create and migrate the tenant database when it does not exist}
        {--section=* : Limit sync to one or more sections}';

    protected $description = 'Sync shared central data such as catalog items, settings, gateways, themes, email templates, and pages into tenant databases';

    public function __construct(protected TenantCatalogSyncService $syncService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $identifier = $this->argument('tenant');
        $syncAll = (bool) $this->option('all');
        $ensureDatabase = (bool) $this->option('ensure-database');
        $sections = $this->option('section') ?: null;

        if ($syncAll && filled($identifier)) {
            $this->error('Use either the tenant argument or --all, not both.');

            return self::INVALID;
        }

        $tenants = $syncAll || blank($identifier)
            ? Tenant::query()->orderBy('id')->get()
            : collect([$this->resolveTenant((string) $identifier)]);

        if ($tenants->isEmpty()) {
            $this->warn('No tenants found to sync.');

            return self::SUCCESS;
        }

        $rows = [];
        $failures = 0;

        foreach ($tenants as $tenant) {
            try {
                if ($ensureDatabase) {
                    $this->prepareTenantDatabase($tenant);
                }

                $summary = $this->syncService->syncForTenant($tenant, $sections);
                $rows[] = array_merge(['tenant' => $tenant->id], $summary);
                $this->info("Synced tenant {$tenant->id}.");
            } catch (TenantDatabaseDoesNotExistException $exception) {
                $failures++;
                $this->error("Failed syncing tenant {$tenant->id}: {$exception->getMessage()} Use --ensure-database to create and migrate the tenant database first.");

                if (! $syncAll && filled($identifier)) {
                    return self::FAILURE;
                }
            } catch (Throwable $exception) {
                $failures++;
                $this->error("Failed syncing tenant {$tenant->id}: {$exception->getMessage()}");

                if (! $syncAll && filled($identifier)) {
                    return self::FAILURE;
                }
            }
        }

        if ($rows !== []) {
            $this->newLine();
            $this->table(array_keys($rows[0]), array_map(fn (array $row) => array_values($row), $rows));
        }

        return $failures === 0 ? self::SUCCESS : self::FAILURE;
    }

    protected function resolveTenant(string $identifier): Tenant
    {
        $tenant = Tenant::query()
            ->where('id', $identifier)
            ->orWhereJsonContains('data->slug', $identifier)
            ->orWhereJsonContains('data->email', $identifier)
            ->orWhereHas('domains', fn ($query) => $query->where('domain', $identifier))
            ->first();

        if (! $tenant) {
            throw new RuntimeException("Tenant [{$identifier}] was not found.");
        }

        return $tenant;
    }

    protected function prepareTenantDatabase(Tenant $tenant): void
    {
        try {
            app()->call([new CreateDatabase($tenant), 'handle']);
            $this->line("Prepared tenant database for {$tenant->id}.");
        } catch (Throwable $exception) {
            if (! str_contains($exception->getMessage(), 'already exists')) {
                throw $exception;
            }
        }

        app()->call([new MigrateDatabase($tenant), 'handle']);
    }
}
