<?php

namespace App\Services;

use App\Enums\FileStorageType;
use App\Enums\FileType;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class ProductMediaService
{
    private const DERIVATIVES = [
        'primary_large' => 1600,
        'primary_medium' => 960,
        'primary_thumb' => 360,
    ];

    public function syncPrimaryImage(Product $product, UploadedFile $uploadedFile): void
    {
        $this->purgePrimaryImage($product);

        $directory = sprintf('products/%s/primary', $product->getKey());
        $disk = FileStorageType::Public ->value;
        $originalExtension = strtolower($uploadedFile->getClientOriginalExtension() ?: 'jpg');
        $originalName = Str::uuid()->toString();

        $originalPath = Storage::disk($disk)->putFileAs($directory, $uploadedFile, sprintf('%s-original.%s', $originalName, $originalExtension));

        $product->files()->create([
            'key' => 'primary_original',
            'path' => $originalPath,
            'storage_type' => $disk,
            'file_type' => FileType::Image,
            'mime_type' => $uploadedFile->getMimeType(),
            'extension' => $originalExtension,
            'size' => $uploadedFile->getSize(),
        ]);

        $manager = new ImageManager(new Driver());

        foreach (self::DERIVATIVES as $key => $maxDimension) {
            $image = $manager->read($uploadedFile->getRealPath())->scaleDown(width: $maxDimension, height: $maxDimension);
            $encoded = $image->toJpeg(82);
            $path = sprintf('%s/%s-%s.jpg', $directory, $originalName, Str::after($key, 'primary_'));

            Storage::disk($disk)->put($path, (string) $encoded);

            $product->files()->create([
                'key' => $key,
                'path' => $path,
                'storage_type' => $disk,
                'file_type' => FileType::Image,
                'mime_type' => 'image/jpeg',
                'extension' => 'jpg',
                'size' => Storage::disk($disk)->size($path),
            ]);
        }
    }

    public function purgePrimaryImage(Product $product): void
    {
        $product->files()
            ->where('key', 'like', 'primary_%')
            ->get()
            ->each
            ->delete();
    }

    public function addGalleryFile(Product $product, UploadedFile $uploadedFile): void
    {
        $directory = sprintf('products/%s/gallery', $product->getKey());
        $disk = FileStorageType::Public ->value;
        $mimeType = $uploadedFile->getMimeType();
        $fileType = Str::startsWith($mimeType, 'video/') ? FileType::Video : FileType::Image;
        $originalExtension = strtolower($uploadedFile->getClientOriginalExtension() ?: 'jpg');
        $originalName = Str::uuid()->toString();

        $originalPath = Storage::disk($disk)->putFileAs($directory, $uploadedFile, sprintf('%s.%s', $originalName, $originalExtension));

        $product->files()->create([
            'key' => 'gallery',
            'path' => $originalPath,
            'storage_type' => $disk,
            'file_type' => $fileType,
            'mime_type' => $mimeType,
            'extension' => $originalExtension,
            'size' => $uploadedFile->getSize(),
        ]);

        if ($fileType === FileType::Image) {
            $manager = new ImageManager(new Driver());
            $image = $manager->read($uploadedFile->getRealPath())->scaleDown(width: 480, height: 480);
            $encoded = $image->toJpeg(82);
            $thumbPath = sprintf('%s/%s-thumb.jpg', $directory, $originalName);

            Storage::disk($disk)->put($thumbPath, (string) $encoded);

            $product->files()->create([
                'key' => 'gallery_thumb',
                'path' => $thumbPath,
                'storage_type' => $disk,
                'file_type' => FileType::Image,
                'mime_type' => 'image/jpeg',
                'extension' => 'jpg',
                'size' => Storage::disk($disk)->size($thumbPath),
            ]);
        }
    }

    public function removeGalleryFile(Product $product, int $fileId): void
    {
        $file = $product->files()
            ->whereIn('key', ['gallery', 'gallery_thumb'])
            ->findOrFail($fileId);

        $basename = pathinfo($file->path, PATHINFO_FILENAME);
        $uuid = Str::replaceLast('-thumb', '', $basename);

        $product->files()
            ->whereIn('key', ['gallery', 'gallery_thumb'])
            ->where('path', 'like', '%' . $uuid . '%')
            ->get()
            ->each
            ->delete();
    }

    public function syncVariantImage(ProductVariant $variant, UploadedFile $uploadedFile): void
    {
        $this->purgeVariantImage($variant);

        $directory = sprintf('products/%s/variants/%s', $variant->product_id, $variant->getKey());
        $disk = FileStorageType::Public ->value;
        $originalExtension = strtolower($uploadedFile->getClientOriginalExtension() ?: 'jpg');
        $originalName = Str::uuid()->toString();

        $originalPath = Storage::disk($disk)->putFileAs(
            $directory,
            $uploadedFile,
            sprintf('%s.%s', $originalName, $originalExtension)
        );

        $variant->files()->create([
            'key' => 'variant_thumb',
            'path' => $originalPath,
            'storage_type' => $disk,
            'file_type' => FileType::Image,
            'mime_type' => $uploadedFile->getMimeType(),
            'extension' => $originalExtension,
            'size' => $uploadedFile->getSize(),
        ]);
    }

    public function purgeVariantImage(ProductVariant $variant): void
    {
        $variant->files()
            ->where('key', 'variant_thumb')
            ->get()
            ->each
            ->delete();
    }
}
