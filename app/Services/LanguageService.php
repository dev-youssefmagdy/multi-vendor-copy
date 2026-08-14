<?php

namespace App\Services;

use App\Models\Language;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use App\Enums\FileStorageType;
use App\Enums\FileType;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class LanguageService
{
    public function save(array $attributes, ?Language $language = null): Language
    {
        return DB::transaction(function () use ($attributes, $language) {
            $previousCode = $language?->code;
            $isCreating = $language === null;
            $language ??= new Language();
            $countries = $attributes['countries'] ?? [];
            if (is_string($countries)) {
                $countries = array_filter(array_map('trim', explode(',', $countries)));
            }

            $language->fill([
                'name' => $attributes['name'],
                'code' => strtolower($attributes['code']),
                'native_name' => $attributes['native_name'],
                'direction' => $attributes['direction'],
                'is_default' => (bool) ($attributes['is_default'] ?? false),
                'is_active' => (bool) ($attributes['is_active'] ?? true),
                'is_free' => (bool) ($attributes['is_free'] ?? true),
                'price' => ($attributes['is_free'] ?? true) ? null : (float) ($attributes['price'] ?? 0),
                'countries' => array_values(array_unique(array_filter($countries))),
            ]);

            if ($isCreating) {
                $language->sort_order = ((int) Language::query()->max('sort_order')) + 1;
            }

            $language->save();

            if ($language->is_default) {
                Language::query()->whereKeyNot($language->id)->update(['is_default' => false]);
            }

            // $this->syncLanguageDirectory($language->code, $previousCode);
            // $this->syncLanguageJsonFile($language->code, $previousCode);

            // Handle image removal
            if (!empty($attributes['remove_image'] ?? false)) {
                if ($language->imageFile) {
                    $language->imageFile->delete();
                    $language->forceFill(['image_file_id' => null])->save();
                }
            }

            // Handle image upload (expects UploadedFile / Livewire TemporaryUploadedFile)
            if (!empty($attributes['image']) && $attributes['image'] instanceof UploadedFile) {
                if ($language->imageFile) {
                    $language->imageFile->delete();
                }

                $disk = FileStorageType::Public ->value;
                $directory = sprintf('languages/%s', $language->getKey());
                $originalExtension = strtolower($attributes['image']->getClientOriginalExtension() ?: 'png');
                $filename = Str::uuid()->toString();

                $manager = new ImageManager(new Driver());
                $image = $manager->read($attributes['image']->getRealPath())->scaleDown(width: 800, height: 800);
                $encoded = $image->toPng();
                $path = sprintf('%s/%s.png', $directory, $filename);

                Storage::disk($disk)->put($path, (string) $encoded);

                $file = \App\Models\File::create([
                    'model_type' => Language::class,
                    'model_id' => $language->id,
                    'key' => 'image',
                    'path' => $path,
                    'storage_type' => $disk,
                    'file_type' => FileType::Image,
                    'mime_type' => 'image/png',
                    'extension' => 'png',
                    'size' => Storage::disk($disk)->size($path),
                ]);

                $language->forceFill(['image_file_id' => $file->id])->save();
            }

            return $language->fresh();
        });
    }

    protected function syncLanguageDirectory(string $code, ?string $previousCode = null): void
    {
        $targetPath = lang_path($code);

        if ($previousCode && $previousCode !== $code) {
            $previousPath = lang_path($previousCode);

            if (File::exists($previousPath) && !File::exists($targetPath)) {
                File::moveDirectory($previousPath, $targetPath);
            }
        }

        if (!File::exists($targetPath)) {
            File::makeDirectory($targetPath, 0755, true);
        }

        $fallbackPath = lang_path(config('app.fallback_locale', 'en'));

        if (File::exists($fallbackPath) && $fallbackPath !== $targetPath) {
            foreach (File::allFiles($fallbackPath) as $file) {
                $relativePath = $file->getRelativePathname();
                $destination = $targetPath . DIRECTORY_SEPARATOR . $relativePath;

                if (!File::exists(dirname($destination))) {
                    File::makeDirectory(dirname($destination), 0755, true);
                }

                if (!File::exists($destination)) {
                    File::copy($file->getPathname(), $destination);
                }
            }
        }

        $messagesPath = $targetPath . '/messages.php';

        if (!File::exists($messagesPath)) {
            File::put($messagesPath, "<?php\n\nreturn [\n    // Translation keys\n];\n");
        }
    }

    protected function syncLanguageJsonFile(string $code, ?string $previousCode = null): void
    {
        $targetPath = lang_path($code . '.json');

        if ($previousCode && $previousCode !== $code) {
            $previousPath = lang_path($previousCode . '.json');

            if (File::exists($previousPath) && !File::exists($targetPath)) {
                File::move($previousPath, $targetPath);
            }
        }

        if (File::exists($targetPath)) {
            return;
        }

        $fallbackPath = lang_path(config('app.fallback_locale', 'en') . '.json');

        if (File::exists($fallbackPath) && $fallbackPath !== $targetPath) {
            File::copy($fallbackPath, $targetPath);

            return;
        }

        File::put($targetPath, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL);
    }
}
