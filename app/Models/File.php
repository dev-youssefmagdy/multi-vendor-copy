<?php

namespace App\Models;

use App\Enums\FileStorageType;
use App\Enums\FileType;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class File extends Model
{
    protected $fillable = ['external_id', 'key', 'path', 'storage_type', 'file_type', 'mime_type', 'extension', 'size', 'model_id', 'model_type'];

    protected $appends = ['full_path'];

    protected function casts(): array
    {
        return [
            'storage_type' => FileStorageType::class,
            'file_type' => FileType::class,
            'size' => 'integer',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($file) {
            $model = $file->model_type;
            $getTableName = (new $model)->getTable();
            $fullPath = $getTableName;
            // $file->file_size = $file->path->getSize();

            if ($file->path instanceof UploadedFile) {
                // Uploaded from request
                $uploadedFile = $file->path;
                $file->mime_type ??= $uploadedFile->getMimeType();
                $file->extension ??= $uploadedFile->getClientOriginalExtension();
                $file->size ??= $uploadedFile->getSize();
                $file->file_type ??= static::inferFileType($file->mime_type, $file->extension);
                $file->path = Storage::disk($file->disk())->putFile($fullPath, $uploadedFile);
            } elseif (is_string($file->path) && filter_var($file->path, FILTER_VALIDATE_URL)) {
                $_file = str_replace('/storage/', '', parse_url($file->path, PHP_URL_PATH));

                $getContent = Storage::disk($file->disk())->get($_file);

                $tempPath = tempnam(sys_get_temp_dir(), 'urlfile');
                file_put_contents($tempPath, $getContent);

                $uploadedFile = new UploadedFile(
                    $tempPath,
                    basename($_file),
                    mime_content_type($tempPath),
                    null,
                    true
                );


                $file->mime_type ??= mime_content_type($tempPath) ?: null;
                $file->extension ??= pathinfo($_file, PATHINFO_EXTENSION) ?: null;
                $file->size ??= filesize($tempPath) ?: null;
                $file->file_type ??= static::inferFileType($file->mime_type, $file->extension);
                $file->path = Storage::disk($file->disk())
                    ->putFile($fullPath, $uploadedFile);
            } elseif (is_string($file->path)) {
                $file->extension ??= pathinfo($file->path, PATHINFO_EXTENSION) ?: null;
                $file->file_type ??= static::inferFileType($file->mime_type, $file->extension);
            }
        });

        static::deleting(function ($file) {
            Storage::disk($file->disk())->delete($file->path);
        });
    }

    public function disk(): string
    {
        return $this->storage_type instanceof FileStorageType
            ? $this->storage_type->value
            : ($this->storage_type ?: FileStorageType::Public ->value);
    }

    protected static function inferFileType(?string $mimeType, ?string $extension): FileType
    {
        $mimeType = strtolower((string) $mimeType);
        $extension = strtolower((string) $extension);

        if (str_starts_with($mimeType, 'image/') || in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'], true)) {
            return FileType::Image;
        }

        if (str_starts_with($mimeType, 'video/') || in_array($extension, ['mp4', 'mov', 'webm'], true)) {
            return FileType::Video;
        }

        if (in_array($extension, ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt'], true)) {
            return FileType::Document;
        }

        return FileType::Other;
    }

    public function model()
    {
        return $this->morphTo();
    }

    function scopeKey($q, $value)
    {
        return $q->where('key', $value);
    }

    function getFullPathAttribute()
    {
        if (!$this->path) {
            return null;
        }

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk($this->disk());

        return url($disk->url($this->path));
    }
}
