<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

/**
 * Single entry point for storing raster images: everything uploaded or converted is written as AVIF.
 * SVG, ICO, animated GIF and non-image files pass through unchanged.
 */
class ImageOptimizer
{
    public const QUALITY = 62;

    public const MAX_DIMENSION = 2560;

    private const CONVERTIBLE = ['jpg', 'jpeg', 'jpe', 'png', 'webp', 'bmp', 'gif'];

    public static function isConvertibleExtension(?string $extension): bool
    {
        return in_array(strtolower((string) $extension), self::CONVERTIBLE, true);
    }

    public static function manager(): ImageManager
    {
        return new ImageManager(new Driver());
    }

    /** Encode image bytes/path to AVIF, optionally bounded to $maxDimension. Returns null if not convertible. */
    public static function encode(string $source, ?int $maxDimension = self::MAX_DIMENSION, int $quality = self::QUALITY, bool $isPath = true): ?string
    {
        try {
            $bytes = $isPath ? (string) file_get_contents($source) : $source;
            if (self::isAnimatedGif($bytes)) {
                return null;
            }
            $image = self::manager()->read($source);
            if ($maxDimension) {
                $image = $image->scaleDown(width: $maxDimension, height: $maxDimension);
            }

            return (string) $image->toAvif($quality);
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    /** Store an upload under $directory; returns the stored relative path (same contract as UploadedFile::store). */
    public static function store(UploadedFile $file, string $directory, string $disk = 'public', ?int $maxDimension = self::MAX_DIMENSION): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: '');
        if (self::isConvertibleExtension($extension) && ($avif = self::encode($file->getRealPath(), $maxDimension))) {
            $path = trim($directory, '/') . '/' . Str::random(40) . '.avif';
            Storage::disk($disk)->put($path, $avif);

            return $path;
        }

        return $file->store($directory, $disk);
    }

    /** Like store(), but with a caller-chosen basename (so sibling derivatives can share it). */
    public static function storeAs(UploadedFile $file, string $directory, string $basename, string $disk = 'public', ?int $maxDimension = self::MAX_DIMENSION): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: '');
        $directory = trim($directory, '/');
        if (self::isConvertibleExtension($extension) && ($avif = self::encode($file->getRealPath(), $maxDimension))) {
            $path = $directory . '/' . $basename . '.avif';
            Storage::disk($disk)->put($path, $avif);

            return $path;
        }

        return Storage::disk($disk)->putFileAs($directory, $file, $basename . '.' . ($extension ?: 'bin'));
    }

    /** Store raw image bytes (downloads, generated logos) as AVIF when possible. Returns [path, mime, extension, size]. */
    public static function putBinary(string $contents, string $pathWithoutExtension, string $fallbackExtension, string $disk = 'public', ?int $maxDimension = self::MAX_DIMENSION): array
    {
        $storage = Storage::disk($disk);
        if (self::isConvertibleExtension($fallbackExtension) && ($avif = self::encode($contents, $maxDimension, self::QUALITY, false))) {
            $path = $pathWithoutExtension . '.avif';
            $storage->put($path, $avif);

            return [$path, 'image/avif', 'avif', strlen($avif)];
        }
        $path = $pathWithoutExtension . '.' . $fallbackExtension;
        $storage->put($path, $contents);

        return [$path, null, $fallbackExtension, strlen($contents)];
    }

    /** Convert an already stored file to AVIF next to the original. Returns the new relative path or null. */
    public static function convertStored(string $disk, string $path, bool $deleteOriginal = false): ?string
    {
        $storage = Storage::disk($disk);
        if (!self::isConvertibleExtension(pathinfo($path, PATHINFO_EXTENSION)) || !$storage->exists($path)) {
            return null;
        }
        $avif = self::encode($storage->get($path), self::MAX_DIMENSION, self::QUALITY, false);
        if ($avif === null) {
            return null;
        }
        $newPath = preg_replace('/\.[^.\/]+$/', '', $path) . '.avif';
        $storage->put($newPath, $avif);
        if ($deleteOriginal) {
            $storage->delete($path);
        }

        return $newPath;
    }

    private static function isAnimatedGif(string $bytes): bool
    {
        if (!str_starts_with($bytes, 'GIF8')) {
            return false;
        }

        return preg_match_all('#\x00\x21\xF9\x04.{4}\x00[\x2C\x21]#s', $bytes) > 1;
    }
}
