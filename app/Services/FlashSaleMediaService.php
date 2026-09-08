<?php

namespace App\Services;

use App\Enums\FileStorageType;
use App\Enums\FileType;
use App\Models\Tenant\FlashSale;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class FlashSaleMediaService
{
    public function syncBanner(FlashSale $flashSale, UploadedFile $uploadedFile): void
    {
        $this->purgeBanner($flashSale);

        $directory = sprintf('flash-sales/%s/banner', $flashSale->getKey());
        $disk = FileStorageType::Public ->value;
        $name = Str::uuid()->toString();

        $manager = new ImageManager(new Driver());
        $encoded = $manager->read($uploadedFile->getRealPath())
            ->scaleDown(width: 1600, height: 600)
            ->toJpeg(85);

        $path = sprintf('%s/%s-banner.jpg', $directory, $name);
        Storage::disk($disk)->put($path, (string) $encoded);

        $flashSale->files()->create([
            'key' => 'banner',
            'path' => $path,
            'storage_type' => $disk,
            'file_type' => FileType::Image,
            'mime_type' => 'image/jpeg',
            'extension' => 'jpg',
            'size' => Storage::disk($disk)->size($path),
        ]);

        $flashSale->forceFill([
            'banner_image' => $flashSale->fresh('files')->banner_url,
        ])->save();
    }

    public function purgeBanner(FlashSale $flashSale): void
    {
        $flashSale->files()->where('key', 'banner')->get()->each->delete();

        $flashSale->forceFill([
            'banner_image' => null,
        ])->save();
    }
}
