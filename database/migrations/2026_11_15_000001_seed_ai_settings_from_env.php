<?php

use App\Support\AiSettings;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * AI settings now live in central `app_settings` (Admin → Settings → AI Settings).
 * One-time copy of the values that were configured in .env, so nothing stops working:
 * Laravel .env (config services.openai.*) first, then python-modules/.env.
 * Only keys that are not stored yet are written; existing admin values are never overwritten.
 */
return new class extends Migration
{
    private const PYTHON_ENV = [
        'api_key' => 'OPENAI_API_KEY',
        'base_url' => 'OPENAI_BASE_URL',
        'text_model' => 'OPENAI_TEXT_MODEL',
        'image_model' => 'OPENAI_IMAGE_MODEL',
        'vision_model' => 'OPENAI_VISION_MODEL',
        'search_model' => 'OPENAI_SEARCH_MODEL',
        'embedding_model' => 'OPENAI_IMAGE_EMBEDDING_MODEL',
    ];

    public function up(): void
    {
        $connection = DB::connection(config('tenancy.database.central_connection'));
        $python = $this->readPythonEnv(base_path('python-modules/.env'));

        foreach (AiSettings::FIELDS as $field => [$key, $configKey]) {
            if ($connection->table('app_settings')->where('key', $key)->exists()) {
                continue;
            }

            $value = trim((string) config($configKey, ''));
            if ($value === '' && isset(self::PYTHON_ENV[$field])) {
                $value = trim((string) ($python[self::PYTHON_ENV[$field]] ?? ''));
            }
            if ($value === '') {
                continue;
            }

            $secret = in_array($field, AiSettings::SECRET_FIELDS, true);
            $connection->table('app_settings')->insert([
                'key' => $key,
                'value' => $secret ? Crypt::encryptString($value) : $value,
                'type' => $secret ? 'secret' : 'string',
                'is_public' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Keep the stored settings: they may have been edited in the admin panel since.
    }

    /** Minimal KEY=VALUE reader (no interpolation), so the values never leak into $_ENV. */
    private function readPythonEnv(string $path): array
    {
        if (!is_readable($path)) {
            return [];
        }

        $values = [];
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }
            [$name, $value] = explode('=', $line, 2);
            $value = trim($value);
            if (strlen($value) >= 2 && ($value[0] === '"' || $value[0] === "'") && str_ends_with($value, $value[0])) {
                $value = substr($value, 1, -1);
            }
            $values[trim($name)] = $value;
        }

        return $values;
    }
};
