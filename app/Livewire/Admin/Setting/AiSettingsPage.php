<?php

namespace App\Livewire\Admin\Setting;

use App\Livewire\Admin\Base\ContentPage;
use App\Livewire\Admin\Concerns\InteractsWithAdminUi;
use App\Support\AiSettings;
use Illuminate\Support\Facades\Http;

/**
 * Central admin: OpenAI provider settings used by every AI feature (price finder, social posts,
 * logo generator, visual search, AI translation). Stored in central `app_settings`; the API key
 * is encrypted. See App\Support\AiSettings.
 */
class AiSettingsPage extends ContentPage
{
    use InteractsWithAdminUi;

    public string $apiKey = '';
    public string $baseUrl = '';
    public string $textModel = '';
    public string $imageModel = '';
    public string $visionModel = '';
    public string $searchModel = '';
    public string $embeddingModel = '';
    public string $translationModel = '';

    /** Result of the last "Test connection": ['ok' => bool, 'message' => string] */
    public array $testResult = [];

    private const MODEL_PROPS = [
        'base_url' => 'baseUrl',
        'text_model' => 'textModel',
        'image_model' => 'imageModel',
        'vision_model' => 'visionModel',
        'search_model' => 'searchModel',
        'embedding_model' => 'embeddingModel',
        'translation_model' => 'translationModel',
    ];

    public function mount(): void
    {
        $this->authorizePermission('settings.general.manage');

        foreach (self::MODEL_PROPS as $field => $prop) {
            $this->{$prop} = AiSettings::get($field);
        }
    }

    protected function pageView(): string
    {
        return 'livewire.admin.setting.ai-settings';
    }

    protected function pageMeta(): array
    {
        return [
            'title' => 'AI Settings',
            'badge' => 'Integrations',
            'description' => 'OpenAI API key and models used by every AI feature: price finder, social posts, logo generator, visual search and AI translation. Stored encrypted in the central database.',
        ];
    }

    public function save(): void
    {
        $this->authorizePermission('settings.general.manage');

        $v = $this->validate([
            'apiKey' => ['nullable', 'string', 'max:500', 'regex:/^\S+$/'],
            'baseUrl' => ['required', 'url', 'max:255'],
            'textModel' => ['required', 'string', 'max:100'],
            'imageModel' => ['required', 'string', 'max:100'],
            'visionModel' => ['required', 'string', 'max:100'],
            'searchModel' => ['required', 'string', 'max:100'],
            'embeddingModel' => ['required', 'string', 'max:100'],
            'translationModel' => ['required', 'string', 'max:100'],
        ], ['apiKey.regex' => 'The API key must not contain spaces.']);

        $values = ['api_key' => $v['apiKey'] ?? ''];
        foreach (self::MODEL_PROPS as $field => $prop) {
            $values[$field] = $v[$prop];
        }

        AiSettings::save($values);

        $this->apiKey = '';
        $this->testResult = [];
        $this->toast('AI settings saved. All AI features use them immediately.');
    }

    public function removeKey(): void
    {
        $this->authorizePermission('settings.general.manage');
        AiSettings::forgetApiKey();
        $this->testResult = [];
        $this->toast('Stored API key removed. The .env value (if any) is used as fallback.');
    }

    /** One-token request straight to OpenAI with the stored settings (does not need the Python service). */
    public function testConnection(): void
    {
        $this->authorizePermission('settings.general.manage');

        if (!AiSettings::configured()) {
            $this->testResult = ['ok' => false, 'message' => 'No API key configured yet.'];

            return;
        }

        try {
            $response = Http::baseUrl(rtrim(AiSettings::get('base_url'), '/'))
                ->withToken(AiSettings::apiKey())
                ->acceptJson()
                ->timeout(20)
                ->post('/chat/completions', [
                    'model' => AiSettings::get('text_model'),
                    'messages' => [['role' => 'user', 'content' => 'ping']],
                    'max_tokens' => 1,
                ]);
        } catch (\Throwable $e) {
            $this->testResult = ['ok' => false, 'message' => 'Could not reach OpenAI: ' . $e->getMessage()];

            return;
        }

        if ($response->successful()) {
            $this->testResult = ['ok' => true, 'message' => 'Connected. The key works and the text model "' . AiSettings::get('text_model') . '" answered.'];

            return;
        }

        $code = (string) ($response->json('error.code') ?? $response->json('error.type') ?? '');
        $message = (string) ($response->json('error.message') ?? $response->body());
        $hint = match (true) {
            str_contains($code, 'quota') || str_contains($code, 'credit') => 'The OpenAI organization/project that owns this key has no credits. Add credits to that same organization (Billing), or paste a key from the funded one.',
            $response->status() === 401 => 'The key is invalid or revoked. Create a new key and paste it here.',
            $response->status() === 404 => 'The text model is not available to this key. Pick another model.',
            default => '',
        };

        $this->testResult = ['ok' => false, 'message' => trim('HTTP ' . $response->status() . ($code ? " ({$code})" : '') . ': ' . $message . ' ' . $hint)];
    }

    protected function pageData(): array
    {
        $field = fn (string $f, string $prop, string $label, string $hint) => [
            'prop' => $prop, 'label' => $label, 'hint' => $hint, 'source' => AiSettings::source($f),
        ];

        return array_merge(parent::pageData(), [
            'hasKey' => AiSettings::configured(),
            'keySource' => AiSettings::source('api_key'),
            'maskedKey' => AiSettings::maskedKey(),
            'fields' => [
                $field('base_url', 'baseUrl', 'API base URL', 'Keep https://api.openai.com/v1 unless you use an OpenAI-compatible proxy.'),
                $field('text_model', 'textModel', 'Text model', 'Social post captions and connection test (e.g. gpt-4o-mini).'),
                $field('image_model', 'imageModel', 'Image model', 'Social post images and AI logos (e.g. gpt-image-1, needs organization verification at OpenAI).'),
                $field('vision_model', 'visionModel', 'Vision model', 'Reads product images for price search and visual search (e.g. gpt-4o-mini).'),
                $field('search_model', 'searchModel', 'Web search model', 'Fallback market-price search with the web_search tool (e.g. gpt-4o-mini).'),
                $field('embedding_model', 'embeddingModel', 'Embedding model', 'Visual product search index (e.g. text-embedding-3-small).'),
                $field('translation_model', 'translationModel', 'Translation model', 'AI translation of stores, catalog and language files (e.g. gpt-4.1-mini).'),
            ],
        ]);
    }
}
