<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

class CleanOrphanFilesCommand extends Command
{
    protected $signature = 'storage:clean-orphans
        {tenant? : Limit the tenant pass to one tenant id}
        {--central-only}
        {--tenants-only}
        {--min-age=48 : Ignore files modified within this many hours (in-flight uploads)}
        {--exclude=* : Extra top-level directories to ignore}
        {--delete : Move orphans to storage/app/orphan-trash (default is report only)}
        {--purge : With --delete, unlink orphans permanently instead of quarantining}';

    protected $description = 'Find (and optionally remove) files on the public disk that no database column references';

    private const SKIP_TABLES = ['migrations', 'jobs', 'job_batches', 'failed_jobs', 'cache', 'cache_locks', 'sessions', 'password_reset_tokens', 'telescope_entries'];

    private const TOKEN = '#[A-Za-z0-9_\-./%@:~+]+\.[A-Za-z0-9]{2,5}(?![A-Za-z0-9])#';

    private int $orphans = 0;

    private int $bytes = 0;

    public function handle(): int
    {
        if (! $this->option('tenants-only')) {
            $this->info('Central storage');
            $this->process('central');
        }
        if (! $this->option('central-only')) {
            $tenants = $this->argument('tenant') ? Tenant::whereKey($this->argument('tenant'))->get() : Tenant::all();
            foreach ($tenants as $tenant) {
                $this->info("Tenant {$tenant->getTenantKey()}");
                try {
                    $tenant->run(fn () => $this->process((string) $tenant->getTenantKey()));
                } catch (Throwable $e) {
                    $this->error("  failed: {$e->getMessage()}");
                }
            }
        }

        $this->info(sprintf('%d orphan files, %s%s.', $this->orphans, number_format($this->bytes / 1048576, 1) . ' MB',
            $this->option('delete') ? ($this->option('purge') ? ' purged' : ' quarantined') : ' (report only, nothing removed)'));

        return self::SUCCESS;
    }

    private function process(string $scope): void
    {
        $referenced = $this->referencedPaths();
        $disk = Storage::disk('public');
        $cutoff = time() - ((int) $this->option('min-age')) * 3600;
        $excluded = array_merge(['livewire-tmp', 'orphan-trash'], (array) $this->option('exclude'));
        $byDir = [];

        foreach ($disk->allFiles() as $path) {
            $top = explode('/', $path)[0];
            if (str_starts_with(basename($path), '.') || in_array($top, $excluded, true) || isset($referenced[$path])) {
                continue;
            }
            if ($disk->lastModified($path) > $cutoff) {
                continue;
            }
            $size = $disk->size($path);
            $this->orphans++;
            $this->bytes += $size;
            $byDir[$top] = ($byDir[$top] ?? 0) + 1;

            if ($this->option('delete')) {
                if ($this->option('purge')) {
                    $disk->delete($path);
                } else {
                    $target = storage_path('app/orphan-trash/' . date('Ymd-His') . '/' . $scope . '/' . $path);
                    @mkdir(dirname($target), 0775, true);
                    @rename($disk->path($path), $target);
                }
            }
        }

        arsort($byDir);
        foreach ($byDir as $dir => $count) {
            $this->line(sprintf('  %-30s %d', $dir, $count));
        }
    }

    /** @return array<string, true> disk-relative paths mentioned anywhere in the database */
    private function referencedPaths(): array
    {
        $set = [];
        foreach (Schema::getTables() as $table) {
            $name = $table['name'];
            if (in_array($name, self::SKIP_TABLES, true)) {
                continue;
            }
            foreach (Schema::getColumns($name) as $column) {
                if (! preg_match('/char|text|json/i', (string) $column['type_name'])) {
                    continue;
                }
                $values = DB::table($name)->whereNotNull($column['name'])->where($column['name'], 'like', '%.%')
                    ->select($column['name'])->distinct()->cursor();
                foreach ($values as $row) {
                    $value = $row->{$column['name']};
                    if (! is_string($value) || ! preg_match_all(self::TOKEN, str_replace('\\/', '/', $value), $m)) {
                        continue;
                    }
                    foreach ($m[0] as $token) {
                        $set[$this->relative($token)] = true;
                    }
                }
            }
        }

        return $set;
    }

    private function relative(string $token): string
    {
        $path = urldecode(ltrim(parse_url($token, PHP_URL_PATH) ?: $token, '/'));
        foreach (['storage/', 'tenancy/assets/'] as $prefix) {
            if (($pos = strpos($path, $prefix)) !== false) {
                return substr($path, $pos + strlen($prefix));
            }
        }

        return $path;
    }
}
