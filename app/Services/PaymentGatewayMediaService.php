<?php

namespace App\Services;

use App\Enums\FileStorageType;
use App\Enums\FileType;
use App\Models\File;
use App\Models\PaymentGateway;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class PaymentGatewayMediaService
{
    public function syncLogo(PaymentGateway $gateway, UploadedFile $uploadedFile): void
    {
        $this->purgeLogo($gateway);

        $disk      = FileStorageType::Public->value;
        $directory = sprintf('payment-gateways/%s', $gateway->getKey());
        $path      = sprintf('%s/%s-logo.png', $directory, Str::uuid()->toString());

        $manager = new ImageManager(new Driver());
        $encoded = $manager->read($uploadedFile->getRealPath())
            ->scaleDown(width: 400, height: 400)
            ->toPng();

        Storage::disk($disk)->put($path, (string) $encoded);

        $file = File::create([
            'model_type'   => PaymentGateway::class,
            'model_id'     => $gateway->id,
            'key'          => 'logo',
            'path'         => $path,
            'storage_type' => $disk,
            'file_type'    => FileType::Image,
            'mime_type'    => 'image/png',
            'extension'    => 'png',
            'size'         => Storage::disk($disk)->size($path),
        ]);

        $gateway->forceFill(['logo_file_id' => $file->id])->save();
    }

    public function purgeLogo(PaymentGateway $gateway): void
    {
        if ($gateway->logoFile) {
            $gateway->logoFile->delete();
            $gateway->forceFill(['logo_file_id' => null])->save();
        }
    }
}
