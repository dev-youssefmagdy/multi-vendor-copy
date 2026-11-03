<?php

namespace App\Console\Commands;

use App\Jobs\ConvertImagesToAvifJob;
use App\Models\Tenant;
use Illuminate\Console\Command;

class ConvertImagesToAvifCommand extends Command
{
    protected $signature = 'images:convert-avif
        {tenant? : Limit the tenant pass to one tenant id}
        {--central-only : Only convert the central database and storage}
        {--tenants-only : Only convert tenant databases and storage}
        {--keep-originals : Keep the old jpg/png/webp files after the database was updated}
        {--dry-run : Report what would be converted without touching files or the database}
        {--sync : Run the conversion inline instead of dispatching queue jobs}';

    protected $description = 'Queue AVIF conversion of stored raster images and rewrite every database reference (central + tenant databases)';

    public function handle(): int
    {
        $keep = (bool) $this->option('keep-originals');
        $dry = (bool) $this->option('dry-run');
        $dispatch = fn (?string $tenantId) => $this->option('sync')
            ? ConvertImagesToAvifJob::dispatchSync($tenantId, $keep, $dry)
            : ConvertImagesToAvifJob::dispatch($tenantId, $keep, $dry);

        $count = 0;
        if (! $this->option('tenants-only') && ! $this->argument('tenant')) {
            $dispatch(null);
            $count++;
        }

        if (! $this->option('central-only')) {
            $tenants = $this->argument('tenant') ? Tenant::whereKey($this->argument('tenant'))->get() : Tenant::all();
            foreach ($tenants as $tenant) {
                $dispatch((string) $tenant->getTenantKey());
                $count++;
            }
        }

        $this->info($this->option('sync') ? "Ran {$count} conversion job(s) inline." : "Queued {$count} conversion job(s). Results are logged by the queue worker.");

        return self::SUCCESS;
    }
}
