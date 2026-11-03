<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Support\ImageOptimizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ConvertImagesToAvifCommand extends Command
{
    protected $signature = 'images:convert-avif
        {tenant? : Limit the tenant pass to one tenant id}
        {--central-only : Only convert the central database and storage}
        {--tenants-only : Only convert tenant databases and storage}
        {--keep-originals : Keep the old jpg/png/webp files after the database was updated}
        {--dry-run : Report what would be converted without touching files or the database}';

    protected $description = 'Convert stored raster images to AVIF and rewrite every database reference (central + tenant databases)';

    private const SKIP_TABLES = ['migrations', 'jobs', 'job_batches', 'failed_jobs', 'cache', 'cache_locks', 'sessions', 'password_reset_tokens', 'telescope_entries'];

    private const TOKEN = '#[A-Za-z0-9_\-./%@:~+]+\.(?:jpe?g|jpe|png|webp|gif|bmp)(?![A-Za-z0-9])#i';

    /** @var array<string, string|null> relative path => converted relative path (null = not convertible) */
    private array $converted = [];

    private int $filesConverted = 0;

    private int $refsRewritten = 0;

    private int $bytesSaved = 0;

    public function handle(): int
    {
        if (! $this->option('tenants-only')) {
            $this->info('Central database');
            $this->process();
        }

        if (! $this->option('central-only')) {
            $tenants = $this->argument('tenant') ? Tenant::whereKey($this->argument('tenant'))->get() : Tenant::all();
            foreach ($tenants as $tenant) {
                $this->info("Tenant {$tenant->getTenantKey()}");
                try {
                    $tenant->run(fn () => $this->process());
                } catch (Throwable $e) {
                    $this->error("  failed: {$e->getMessage()}");
                }
            }
        }

        $this->info(sprintf('%d files converted, %d database values rewritten, %s saved%s.',
            $this->filesConverted, $this->refsRewritten, number_format($this->bytesSaved / 1048576, 1) . ' MB',
            $this->option('dry-run') ? ' (dry run)' : ''));

        return self::SUCCESS;
    }

    private function process(): void
    {
        $this->converted = [];
        $originals = [];

        // 1. `files` table: path + extension + mime + size are kept consistent.
        if (Schema::hasTable('files')) {
            DB::table('files')->orderBy('id')->chunkById(200, function ($rows) use (&$originals) {
                foreach ($rows as $row) {
                    if (! ImageOptimizer::isConvertibleExtension(pathinfo((string) $row->path, PATHINFO_EXTENSION)) || str_contains((string) $row->path, '://')) {
                        continue;
                    }
                    $new = $this->convert((string) $row->path, $row->storage_type ?: 'public', $originals);
                    if (! $new) {
                        continue;
                    }
                    if (! $this->option('dry-run')) {
                        DB::table('files')->where('id', $row->id)->update([
                            'path' => $new,
                            'extension' => 'avif',
                            'mime_type' => 'image/avif',
                            'size' => Storage::disk($row->storage_type ?: 'public')->size($new),
                        ]);
                    }
                    $this->refsRewritten++;
                }
            });
        }

        // 2. Every other text/json column (settings, banners, logos, template parts, blog HTML, ...).
        foreach (Schema::getTables() as $table) {
            $name = $table['name'];
            if ($name === 'files' || in_array($name, self::SKIP_TABLES, true)) {
                continue;
            }
            foreach (Schema::getColumns($name) as $column) {
                if (! preg_match('/char|text|json/i', (string) $column['type_name'])) {
                    continue;
                }
                $this->rewriteColumn($name, $column['name'], $originals);
            }
        }

        if (! $this->option('dry-run') && ! $this->option('keep-originals')) {
            foreach ($originals as [$disk, $path]) {
                Storage::disk($disk)->delete($path);
            }
        }
    }

    private function rewriteColumn(string $table, string $column, array &$originals): void
    {
        $query = DB::table($table)->whereNotNull($column)->where(function ($q) use ($column) {
            foreach (['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp'] as $ext) {
                $q->orWhere($column, 'like', "%.{$ext}%");
            }
        })->select($column)->distinct();

        foreach ($query->pluck($column) as $value) {
            if (! is_string($value)) {
                continue;
            }
            $plain = str_replace('\\/', '/', $value);
            if (! preg_match_all(self::TOKEN, $plain, $matches)) {
                continue;
            }
            $updated = $value;
            foreach (array_unique($matches[0]) as $token) {
                $relative = $this->relativeStoragePath($token);
                $new = $relative ? $this->convert($relative, 'public', $originals) : null;
                if (! $new) {
                    continue;
                }
                $newToken = preg_replace('/\.[A-Za-z0-9]+$/', '.avif', $token);
                $updated = str_replace([$token, str_replace('/', '\\/', $token)], [$newToken, str_replace('/', '\\/', $newToken)], $updated);
            }
            if ($updated !== $value) {
                if (! $this->option('dry-run')) {
                    DB::table($table)->where($column, $value)->update([$column => $updated]);
                }
                $this->refsRewritten++;
            }
        }
    }

    /** Reduce a stored URL/path ("https://x/storage/a/b.jpg", "/tenancy/assets/a/b.jpg", "a/b.jpg") to a disk-relative path. */
    private function relativeStoragePath(string $token): ?string
    {
        $path = parse_url($token, PHP_URL_PATH) ?: $token;
        $path = urldecode(ltrim($path, '/'));
        foreach (['storage/', 'tenancy/assets/'] as $prefix) {
            if (($pos = strpos($path, $prefix)) !== false) {
                $path = substr($path, $pos + strlen($prefix));
                break;
            }
        }

        return $path !== '' && ! str_contains($path, '..') ? $path : null;
    }

    private function convert(string $path, string $disk, array &$originals): ?string
    {
        $key = $disk . ':' . $path;
        if (array_key_exists($key, $this->converted)) {
            return $this->converted[$key];
        }

        $storage = Storage::disk($disk);
        $target = preg_replace('/\.[^.\/]+$/', '', $path) . '.avif';
        $this->converted[$key] = null;

        if (! ImageOptimizer::isConvertibleExtension(pathinfo($path, PATHINFO_EXTENSION)) || ! $storage->exists($path)) {
            return null;
        }

        $before = $storage->size($path);
        if ($this->option('dry-run')) {
            $this->filesConverted++;

            return $this->converted[$key] = $target;
        }

        $new = ImageOptimizer::convertStored($disk, $path);
        if (! $new) {
            return null;
        }
        $this->filesConverted++;
        $this->bytesSaved += max(0, $before - $storage->size($new));
        $originals[] = [$disk, $path];

        return $this->converted[$key] = $new;
    }
}
