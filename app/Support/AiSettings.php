<?php

namespace App\Support;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

/**
 * AI provider settings (OpenAI key, base URL, models).
 *
 * Source of truth: central `app_settings` (Admin → Settings → AI Settings). The API key is stored
 * encrypted (type = secret). `.env` / config('services.openai.*') is only a fallback for values that
 * were never saved in the admin panel.
 *
 * Consumers:
 *  - OpenAiTranslationService (calls OpenAI directly from PHP)
 *  - every call to the Python AI service (python-modules) via pythonHeaders(): the settings are
 *    forwarded as X-OpenAI-* headers and python-modules/ai_config.py uses them for that request.
 *
 * The Python service URL and its shared X-Service-Token stay in .env (they secure the channel the
 * key travels over).
 */
final class AiSettings
{
    /** setting => [app_settings key, config fallback key, default, python header] */
    public const FIELDS = [
        'api_key' => ['openai_api_key', 'services.openai.api_key', '', 'X-OpenAI-Key'],
        'base_url' => ['openai_base_url', 'services.openai.base_url', 'https://api.openai.com/v1', 'X-OpenAI-Base-Url'],
        'text_model' => ['openai_text_model', 'services.openai.text_model', 'gpt-4o-mini', 'X-OpenAI-Text-Model'],
        'image_model' => ['openai_image_model', 'services.openai.image_model', 'gpt-image-1', 'X-OpenAI-Image-Model'],
        'vision_model' => ['openai_vision_model', 'services.openai.vision_model', 'gpt-4o-mini', 'X-OpenAI-Vision-Model'],
        'search_model' => ['openai_search_model', 'services.openai.search_model', 'gpt-4o-mini', 'X-OpenAI-Search-Model'],
        'embedding_model' => ['openai_embedding_model', 'services.openai.embedding_model', 'text-embedding-3-small', 'X-OpenAI-Embedding-Model'],
        'translation_model' => ['openai_translation_model', 'services.openai.translation_model', 'gpt-4.1-mini', null],
    ];

    public const SECRET_FIELDS = ['api_key'];

    /** @var array<string, string>|null raw DB values, keyed by app_settings key */
    private static ?array $rows = null;

    /** Re-read after this many seconds so long-running queue workers pick up admin changes. */
    private const TTL = 60;

    private static int $loadedAt = 0;

    public static function get(string $field): string
    {
        [$key, $configKey, $default] = self::FIELDS[$field];

        $stored = self::stored($field);
        if ($stored !== '') {
            return $stored;
        }

        $fallback = trim((string) config($configKey, ''));

        return $fallback !== '' ? $fallback : $default;
    }

    public static function apiKey(): string
    {
        return self::get('api_key');
    }

    public static function configured(): bool
    {
        return self::apiKey() !== '';
    }

    /** Value saved in the admin panel only ('' when not saved), decrypted for secrets. */
    public static function stored(string $field): string
    {
        $key = self::FIELDS[$field][0];
        $raw = (string) (self::rows()[$key] ?? '');

        if ($raw === '' || !in_array($field, self::SECRET_FIELDS, true)) {
            return trim($raw);
        }

        try {
            return trim(Crypt::decryptString($raw));
        } catch (\Throwable $e) {
            Log::warning('AiSettings: stored secret could not be decrypted (APP_KEY changed?)', ['field' => $field]);

            return '';
        }
    }

    /** Where the effective value comes from: 'database', 'env' or 'default'. */
    public static function source(string $field): string
    {
        if (self::stored($field) !== '') {
            return 'database';
        }

        return trim((string) config(self::FIELDS[$field][1], '')) !== '' ? 'env' : 'default';
    }

    /** Persist values from the admin page. Blank secret = keep the stored one. */
    public static function save(array $values): void
    {
        foreach (self::FIELDS as $field => [$key]) {
            if (!array_key_exists($field, $values)) {
                continue;
            }

            $value = trim((string) $values[$field]);
            $isSecret = in_array($field, self::SECRET_FIELDS, true);

            if ($isSecret && $value === '') {
                continue;
            }

            // Written directly (not via AppSettingService) so secrets are never pushed to tenant databases.
            AppSetting::query()->updateOrCreate(['key' => $key], [
                'value' => $isSecret ? Crypt::encryptString($value) : $value,
                'type' => $isSecret ? 'secret' : 'string',
                'is_public' => false,
            ]);
        }

        self::flush();
    }

    public static function forgetApiKey(): void
    {
        AppSetting::query()->where('key', self::FIELDS['api_key'][0])->delete();
        self::flush();
    }

    /** Masked key for display, e.g. "sk-proj…a1B2". */
    public static function maskedKey(): string
    {
        $key = self::apiKey();

        return $key === '' ? '' : substr($key, 0, 7) . '…' . substr($key, -4);
    }

    /**
     * Headers for requests to the Python AI service: shared service token + AI settings.
     *
     * @return array<string, string>
     */
    public static function pythonHeaders(): array
    {
        $headers = [];

        $token = (string) config('services.python_modules.token', '');
        if ($token !== '') {
            $headers['X-Service-Token'] = $token;
        }

        foreach (self::FIELDS as $field => [, , , $header]) {
            if ($header === null) {
                continue;
            }
            $value = self::get($field);
            if ($value !== '') {
                $headers[$header] = $value;
            }
        }

        return $headers;
    }

    public static function flush(): void
    {
        self::$rows = null;
    }

    private static function rows(): array
    {
        if (self::$rows !== null && (time() - self::$loadedAt) < self::TTL) {
            return self::$rows;
        }

        try {
            $keys = array_column(self::FIELDS, 0);
            self::$loadedAt = time();
            self::$rows = AppSetting::query()->whereIn('key', $keys)->pluck('value', 'key')->map(fn ($v) => (string) $v)->all();
        } catch (\Throwable $e) {
            // DB not reachable (installer, early boot): fall back to config without caching the failure.
            Log::warning('AiSettings: could not read app_settings', ['error' => $e->getMessage()]);

            return [];
        }

        return self::$rows;
    }
}
