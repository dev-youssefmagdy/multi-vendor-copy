<?php

namespace App\Services\Tenant;

use App\Enums\FileStorageType;
use App\Enums\FileType;
use App\Models\Tenant\Catalog\MergedCategory;
use App\Models\Tenant\Category;
use App\Models\Tenant\File;
use App\Repositories\Tenant\Catalog\CategoryAssignments;
use App\Repositories\Tenant\Catalog\CategoryCatalog;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Imagick\Driver;
use Intervention\Image\ImageManager;

class TenantCategoryMediaService
{
    public function syncThumb(Category $category, UploadedFile $uploadedFile): void
    {
        // Dedup phase 4: an own merged category is the tenant row behind it.
        if ($category instanceof MergedCategory && ! $category->isCentral()) {
            $category = Category::query()->findOrFail($category->source_id);
        }

        $this->purgeThumb($category);

        $directory = $category instanceof MergedCategory
            ? sprintf('categories/central-%s', $category->source_id)
            : sprintf('categories/%s', $category->getKey());
        $disk      = FileStorageType::Public->value;
        $name      = Str::uuid()->toString();

        $manager = new ImageManager(new Driver());
        $encoded = $manager->read($uploadedFile->getRealPath())
            ->scaleDown(width: 600, height: 600)
            ->toAvif(\App\Support\ImageOptimizer::QUALITY);

        $path = sprintf('%s/%s-thumb.avif', $directory, $name);
        Storage::disk($disk)->put($path, (string) $encoded);

        $attributes = [
            'key'          => 'thumb',
            'path'         => $path,
            'storage_type' => $disk,
            'file_type'    => FileType::Image,
            'mime_type'    => 'image/avif',
            'extension'    => 'avif',
            'size'         => Storage::disk($disk)->size($path),
        ];

        if ($category instanceof MergedCategory) {
            // Central category (dedup phase 4): a tenant file owned by `central_category` + the override's thumb_file_id.
            $file = File::query()->create($attributes + [
                'model_type' => CategoryCatalog::CENTRAL_FILE_TYPE,
                'model_id' => $category->source_id,
            ]);
            app(CategoryAssignments::class)->setThumb($category, (int) $file->id);

            return;
        }

        $category->files()->create($attributes);
    }

    public function purgeThumb(Category $category): void
    {
        if ($category instanceof MergedCategory) {
            if (! $category->isCentral()) {
                $this->purgeThumb(Category::query()->findOrFail($category->source_id));

                return;
            }

            // Back to the central thumb; the vendor files of this central category are removed.
            File::query()->where('model_type', CategoryCatalog::CENTRAL_FILE_TYPE)->where('model_id', $category->source_id)
                ->where('key', 'thumb')->get()->each->delete();
            app(CategoryAssignments::class)->setThumb($category, null);

            return;
        }

        $category->files()->where('key', 'thumb')->get()->each->delete();
    }
}
