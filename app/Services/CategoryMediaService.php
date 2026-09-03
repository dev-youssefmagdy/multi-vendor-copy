<?php

namespace App\Services;

use App\Enums\FileStorageType;
use App\Enums\FileType;
use App\Models\Category;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class CategoryMediaService
{
    public function syncThumb(Category $category, UploadedFile $uploadedFile): void
    {
        $this->purgeThumb($category);

        $directory = sprintf('categories/%s', $category->getKey());
        $disk      = FileStorageType::Public->value;
        $name      = Str::uuid()->toString();

        $manager = new ImageManager(new Driver());
        $encoded = $manager->read($uploadedFile->getRealPath())
            ->scaleDown(width: 600, height: 600)
            ->toJpeg(85);

        $path = sprintf('%s/%s-thumb.jpg', $directory, $name);
        Storage::disk($disk)->put($path, (string) $encoded);

        $category->files()->create([
            'key'          => 'thumb',
            'path'         => $path,
            'storage_type' => $disk,
            'file_type'    => FileType::Image,
            'mime_type'    => 'image/jpeg',
            'extension'    => 'jpg',
            'size'         => Storage::disk($disk)->size($path),
        ]);
    }

    public function purgeThumb(Category $category): void
    {
        $category->files()->where('key', 'thumb')->get()->each->delete();
    }
}
