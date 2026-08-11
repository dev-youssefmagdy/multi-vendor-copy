<?php

namespace App\Services;

use App\Enums\ContentStatus;
use App\Enums\FileStorageType;
use App\Enums\FileType;
use App\Models\BlogPost;
use App\Models\Language;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class BlogPostService
{
    public function save(array $attributes, ?BlogPost $post = null): BlogPost
    {
        return DB::transaction(function () use ($attributes, $post) {
            $post ??= new BlogPost();
            $status = $attributes['status'];
            $publishedAt = $attributes['published_at'] ?? null;

            if ($status === ContentStatus::Published->value && blank($publishedAt)) {
                $publishedAt = now();
            }

            $defaultLocale = Language::query()->where('is_default', true)->value('code') ?? 'en';
            $defaultTitle = $attributes['translations'][$defaultLocale]['title'] ?? '';

            // Build per-locale translations including auto-generated slug
            $translationsToSync = collect($attributes['translations'] ?? [])
                ->map(fn($fields, $locale) => [
                    'title' => $fields['title'] ?? '',
                    'slug' => filled($fields['slug'] ?? '')
                        ? Str::slug($fields['slug'])
                        : Str::slug($fields['title'] ?? ''),
                    'excerpt' => $fields['excerpt'] ?? '',
                    'content' => $fields['content'] ?? '',
                ])
                ->all();

            // Canonical DB slug comes from the default locale
            $canonicalSlug = $translationsToSync[$defaultLocale]['slug']
                ?: Str::slug($defaultTitle);

            $post->fill([
                'blog_category_id' => $attributes['blog_category_id'] ?: null,
                'slug' => $canonicalSlug,
                'status' => $status,
                'published_at' => $publishedAt,
            ]);
            $post->save();

            $post->syncTranslations($translationsToSync);

            // Handle image removal
            if (!empty($attributes['remove_image'] ?? false)) {
                $post->files()->where('key', 'image')->get()->each->delete();
            }

            // Handle image upload
            if (!empty($attributes['image']) && $attributes['image'] instanceof UploadedFile) {
                // Delete existing image file record
                $post->files()->where('key', 'image')->get()->each->delete();

                $disk = FileStorageType::Public->value;
                $directory = sprintf('blog-posts/%s', $post->getKey());

                $manager = new ImageManager(new Driver());
                $image = $manager->read($attributes['image']->getRealPath())->scaleDown(width: 1200, height: 800);
                $encoded = $image->toPng();
                $filename = Str::uuid()->toString();
                $path = sprintf('%s/%s.png', $directory, $filename);

                Storage::disk($disk)->put($path, (string) $encoded);

                $post->files()->create([
                    'key' => 'image',
                    'path' => $path,
                    'storage_type' => $disk,
                    'file_type' => FileType::Image,
                    'mime_type' => 'image/png',
                    'extension' => 'png',
                    'size' => Storage::disk($disk)->size($path),
                ]);
            }

            return $post->fresh(['category', 'translations.language']);
        });
    }

    public function delete(BlogPost $post): void
    {
        $post->delete();
    }
}
