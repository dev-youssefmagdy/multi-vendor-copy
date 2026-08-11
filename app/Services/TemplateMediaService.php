<?php

namespace App\Services;

use App\Enums\FileStorageType;
use App\Enums\FileType;
use App\Models\File;
use App\Models\Template;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class TemplateMediaService
{
    public function syncPreview(Template $template, UploadedFile $uploadedFile): void
    {
        $this->purgePreview($template);

        $disk      = FileStorageType::Public->value;
        $directory = sprintf('templates/%s', $template->getKey());
        $path      = sprintf('%s/%s-preview.jpg', $directory, Str::uuid()->toString());

        $manager = new ImageManager(new Driver());
        $encoded = $manager->read($uploadedFile->getRealPath())
            ->scaleDown(width: 1200, height: 800)
            ->toJpeg(85);

        Storage::disk($disk)->put($path, (string) $encoded);

        $file = File::create([
            'model_type'   => Template::class,
            'model_id'     => $template->id,
            'key'          => 'preview',
            'path'         => $path,
            'storage_type' => $disk,
            'file_type'    => FileType::Image,
            'mime_type'    => 'image/jpeg',
            'extension'    => 'jpg',
            'size'         => Storage::disk($disk)->size($path),
        ]);

        $template->forceFill(['preview_file_id' => $file->id])->save();
    }

    public function purgePreview(Template $template): void
    {
        if ($template->previewFile) {
            $template->previewFile->delete();
            $template->forceFill(['preview_file_id' => null])->save();
        }
    }
}
