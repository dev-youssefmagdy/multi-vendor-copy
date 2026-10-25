<?php

namespace App\Models\Tenant;

use App\Enums\FileStorageType;
use App\Enums\FileType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

class File extends Model
{
    protected $fillable = ['key', 'path', 'storage_type', 'file_type', 'mime_type', 'extension', 'size', 'model_id', 'model_type', 'sort_order'];

    protected $appends = ['full_path'];

    protected static function booted(): void
    {
        static::deleting(function (self $file) {
            if ($file->path) {
                Storage::disk($file->disk())->delete($file->path);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'storage_type' => FileStorageType::class,
            'file_type' => FileType::class,
            'size' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function model()
    {
        return $this->morphTo();
    }

    public function disk(): string
    {
        return $this->storage_type instanceof FileStorageType
            ? $this->storage_type->value
            : ($this->storage_type ?: FileStorageType::Public ->value);
    }

    /**
     * Request-scoped memo of computed URLs ("tenant|storage_type|path" => url);
     * see App\Models\File::getFullPathAttribute(). Reset whenever the current
     * request instance changes.
     */
    private static array $fullPathMemo = [];
    private static ?\WeakReference $fullPathMemoOwner = null;

    public function getFullPathAttribute(): ?string
    {
        $path = $this->attributes['path'] ?? null;

        if (!$path) {
            return null;
        }

        if (!is_string($path)) {
            return $this->computeFullPath($path);
        }

        $request = app('request');
        if (self::$fullPathMemoOwner?->get() !== $request || count(self::$fullPathMemo) >= 20000) {
            self::$fullPathMemo = [];
            self::$fullPathMemoOwner = \WeakReference::create($request);
        }

        $storageType = $this->attributes['storage_type'] ?? null;
        $memoKey = (tenancy()->tenant?->getTenantKey() ?? '') . '|' . ($storageType instanceof \BackedEnum ? $storageType->value : (string) $storageType) . '|' . $path;

        return self::$fullPathMemo[$memoKey] ??= $this->computeFullPath($path);
    }

    private function computeFullPath($path): ?string
    {
        if ($this->disk() === FileStorageType::Public->value) {
            return tenant_asset($path);
        }

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk($this->disk());

        return url($disk->url($path));
    }

    /**
     * Absolute filesystem path, for callers (e.g. the Python AI service) that
     * need to read the file directly instead of fetching it over HTTP.
     * Returns null when the configured disk has no local path (e.g. S3).
     */
    public function getLocalPathAttribute(): ?string
    {
        if (!$this->path) {
            return null;
        }

        $disk = Storage::disk($this->disk());

        if (!method_exists($disk, 'path')) {
            return null;
        }

        $path = $disk->path($this->path);

        return is_file($path) ? $path : null;
    }
}
