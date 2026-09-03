<?php

namespace App\Services;

use App\Models\Language;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use InvalidArgumentException;

class TranslationFileService
{
    public function locales(): array
    {
        $databaseLocales = Language::query()
            ->orderByDesc('is_default')

            ->pluck('code')
            ->filter()
            ->values()
            ->all();

        $directoryLocales = collect(File::directories(lang_path()))
            ->map(fn(string $path) => basename($path))
            ->filter()
            ->values()
            ->all();

        $jsonLocales = collect(File::files(lang_path()))
            ->filter(fn(\SplFileInfo $file) => $file->getExtension() === 'json')
            ->map(fn(\SplFileInfo $file) => $file->getBasename('.json'))
            ->filter()
            ->values()
            ->all();

        return collect($databaseLocales)
            ->merge([$this->fallbackLocale()])
            ->merge($directoryLocales)
            ->merge($jsonLocales)
            ->filter(fn(string $locale) => preg_match('/^[A-Za-z0-9_-]+$/', $locale) === 1)
            ->unique()
            ->values()
            ->all();
    }

    public function resources(): array
    {
        $locales = $this->locales();

        $phpFiles = collect($locales)
            ->flatMap(function (string $locale) {
                $directory = lang_path($locale);

                if (!File::isDirectory($directory)) {
                    return [];
                }

                return collect(File::files($directory))
                    ->filter(fn(\SplFileInfo $file) => $file->getExtension() === 'php')
                    ->map(fn(\SplFileInfo $file) => $file->getBasename('.php'))
                    ->all();
            })
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $resources = [
            [
                'key' => 'json',
                'label' => 'JSON Translations',
                'type' => 'json',
            ]
        ];

        foreach ($phpFiles as $fileName) {
            $resources[] = [
                'key' => 'php:' . $fileName,
                'label' => $fileName . '.php',
                'type' => 'php',
            ];
        }

        return $resources;
    }

    public function read(string $resourceKey): array
    {
        $resource = $this->parseResourceKey($resourceKey);
        $locales = $this->locales();
        $translations = [];

        foreach ($locales as $locale) {
            $translations[$locale] = $resource['type'] === 'json'
                ? $this->readJsonTranslations($locale)
                : $this->readPhpTranslations($locale, $resource['name']);
        }

        $keys = collect($translations)
            ->flatMap(fn(array $entries) => array_keys($entries))
            ->filter(fn(mixed $key) => is_string($key) && $key !== '')
            ->unique()
            ->sort()
            ->values()
            ->all();

        $rows = [];

        foreach ($keys as $key) {
            $key = (string) $key;
            $values = [];

            try {
                foreach ($locales as $locale) {

                    $val = $translations[$locale][$key] ?? '';
                    $values[$locale] = is_array($val) ? '' : (string) $val;
                }
            } catch (\Exception $e) {
                info("Error reading translation for key '{$key}' in resource '{$resourceKey}': " . $e->getMessage());
                info("Translations array for locale '{$locale}': " . var_export($translations[$locale] ?? null, true));
                // $translations
                info("Full translations data: " . var_export($translations, true));
                continue;
            }

            $rows[] = [
                'key' => $key,
                'values' => $values,
            ];
        }

        return [
            'resource' => $resource,
            'locales' => $locales,
            'rows' => $rows,
        ];
    }

    public function save(string $resourceKey, array $rows): void
    {
        $resource = $this->parseResourceKey($resourceKey);
        $locales = $this->locales();

        foreach ($locales as $locale) {
            $entries = [];

            foreach ($rows as $row) {
                $key = trim((string) ($row['key'] ?? ''));

                if ($key === '') {
                    continue;
                }

                $value = trim((string) ($row['values'][$locale] ?? ''));

                if ($resource['type'] === 'json') {
                    if ($value === '') {
                        if ($locale === $this->fallbackLocale()) {
                            $entries[$key] = $key;
                        }

                        continue;
                    }

                    $entries[$key] = $value;

                    continue;
                }

                if ($value !== '') {
                    $entries[$key] = $value;
                }
            }

            if ($resource['type'] === 'json') {
                $this->writeJsonTranslations($locale, $entries);
                continue;
            }

            $this->writePhpTranslations($locale, $resource['name'], $entries);
        }
    }

    public function createPhpResource(string $fileName): string
    {
        $normalized = Str::of($fileName)
            ->trim()
            ->replace('.php', '')
            ->snake('-')
            ->replace('-', '_')
            ->value();

        if ($normalized === '' || preg_match('/^[A-Za-z0-9_]+$/', $normalized) !== 1) {
            throw new InvalidArgumentException('Translation file names may only contain letters, numbers, and underscores.');
        }

        foreach ($this->locales() as $locale) {
            $path = lang_path($locale . '/' . $normalized . '.php');
            File::ensureDirectoryExists(dirname($path));

            if (!File::exists($path)) {
                File::put($path, $this->phpFileContents([]));
            }
        }

        return 'php:' . $normalized;
    }

    protected function parseResourceKey(string $resourceKey): array
    {
        if ($resourceKey === 'json') {
            return [
                'key' => 'json',
                'label' => 'JSON Translations',
                'type' => 'json',
                'name' => null,
            ];
        }

        if (str_starts_with($resourceKey, 'php:')) {
            $name = Str::after($resourceKey, 'php:');

            if ($name === '' || preg_match('/^[A-Za-z0-9_]+$/', $name) !== 1) {
                throw new InvalidArgumentException('Invalid translation file selection.');
            }

            return [
                'key' => $resourceKey,
                'label' => $name . '.php',
                'type' => 'php',
                'name' => $name,
            ];
        }

        throw new InvalidArgumentException('Unsupported translation resource.');
    }

    protected function readJsonTranslations(string $locale): array
    {
        $path = lang_path($locale . '.json');

        if (!File::exists($path)) {
            return [];
        }

        $decoded = json_decode((string) File::get($path), true);

        return is_array($decoded) ? $decoded : [];
    }

    protected function writeJsonTranslations(string $locale, array $entries): void
    {
        ksort($entries);

        $path = lang_path($locale . '.json');
        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode($entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL);
    }

    protected function readPhpTranslations(string $locale, string $fileName): array
    {
        $path = lang_path($locale . '/' . $fileName . '.php');

        if (!File::exists($path)) {
            return [];
        }

        $translations = require $path;

        if (!is_array($translations)) {
            return [];
        }

        return Arr::dot($translations);
    }

    protected function writePhpTranslations(string $locale, string $fileName, array $entries): void
    {
        $path = lang_path($locale . '/' . $fileName . '.php');
        File::ensureDirectoryExists(dirname($path));

        $nested = Arr::undot($entries);
        $this->sortNestedArray($nested);

        File::put($path, $this->phpFileContents($nested));
    }

    protected function phpFileContents(array $translations): string
    {
        return "<?php\n\nreturn " . var_export($translations, true) . ";\n";
    }

    protected function sortNestedArray(array &$items): void
    {
        ksort($items);

        foreach ($items as &$value) {
            if (is_array($value)) {
                $this->sortNestedArray($value);
            }
        }
    }

    protected function fallbackLocale(): string
    {
        return config('app.fallback_locale', 'en');
    }
}
