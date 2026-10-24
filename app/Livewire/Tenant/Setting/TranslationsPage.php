<?php

namespace App\Livewire\Tenant\Setting;

use App\Jobs\Tenant\TranslateStoreJob;
use App\Livewire\Tenant\Base\TenantPage;
use App\Livewire\Tenant\Concerns\InteractsWithTenantUi;
use App\Models\Tenant\Language;
use App\Services\OpenAiTranslationService;
use App\Services\Tenant\PlanLimitService;
use App\Services\Tenant\TenantTranslationService;
use RuntimeException;

class TranslationsPage extends TenantPage
{
    use InteractsWithTenantUi;

    public ?int $selectedLanguageId = null;
    public string $search = '';
    public bool $showOnlyMissing = false;

    public int $page = 1;
    public int $perPage = 25;

    public array $selectedKeys = [];

    protected $queryString = ['page'];

    public function mount(): void
    {
        $this->selectedLanguageId = Language::query()->where('is_default', true)->value('id')
            ?? Language::query()->where('is_active', true)->value('id');
    }

    protected function pageView(): string
    {
        return 'livewire.tenant.setting.translations-page';
    }

    protected function pageMeta(): array
    {
        return [
            'title' => 'Translations',
            'badge' => 'Settings',
            'description' => 'Manage static UI-string translations for your storefront. Manual edits always take priority over defaults.',
        ];
    }

    public function updatedSelectedLanguageId(): void
    {
        $this->page = 1;
        $this->selectedKeys = [];
    }

    public function updatedSearch(): void
    {
        $this->page = 1;
    }

    public function updatedShowOnlyMissing(): void
    {
        $this->page = 1;
    }

    public function setPage(int $page): void
    {
        $this->page = max(1, (int) $page);
    }

    public function saveKey(string $key, string $value, TenantTranslationService $service): void
    {
        try {
            $service->saveOverride((string) $this->selectedLanguageId, $key, $value);
            $this->toast('Translation saved successfully.');
        } catch (RuntimeException $e) {
            $this->toast($e->getMessage(), 'error');
        }
    }

    public function translateKeyWithAi(string $key, TenantTranslationService $service, OpenAiTranslationService $ai): void
    {
        if (!$this->aiTranslationAllowed()) {
            return;
        }

        $limitService = app(PlanLimitService::class);
        if (!$limitService->canPerform(tenant(), PlanLimitService::FEATURE_AI_CALLS)) {
            $this->toast($limitService->errorMessage(PlanLimitService::FEATURE_AI_CALLS), 'error');
            return;
        }

        $language = Language::query()->find($this->selectedLanguageId);

        if (!$language) {
            return;
        }

        try {
            $service->translateKeyWithAi($language, $key, $ai);
            $limitService->incrementCounter(tenant(), 'ai_calls_count');
            $this->toast('Key translated with AI successfully.');
        } catch (RuntimeException $e) {
            $this->toast($e->getMessage(), 'error');
        }
    }

    public function translateSelectedWithAi(TenantTranslationService $service, OpenAiTranslationService $ai): void
    {
        if (!$this->aiTranslationAllowed()) {
            return;
        }

        if ($this->selectedKeys === []) {
            $this->toast('Select at least one key to translate.', 'error');
            return;
        }

        $limitService = app(PlanLimitService::class);
        if (!$limitService->canPerform(tenant(), PlanLimitService::FEATURE_AI_CALLS)) {
            $this->toast($limitService->errorMessage(PlanLimitService::FEATURE_AI_CALLS), 'error');
            return;
        }

        $language = Language::query()->find($this->selectedLanguageId);

        if (!$language) {
            return;
        }

        $count = $service->translateKeysWithAi($language, $this->selectedKeys, $ai);
        $limitService->incrementCounter(tenant(), 'ai_calls_count');
        $this->selectedKeys = [];
        $this->toast("{$count} key(s) translated with AI successfully.");
    }

    public function translateStore(): void
    {
        if (!$this->aiTranslationAllowed()) {
            return;
        }

        $limitService = app(PlanLimitService::class);
        if (!$limitService->canPerform(tenant(), PlanLimitService::FEATURE_AI_CALLS)) {
            $this->toast($limitService->errorMessage(PlanLimitService::FEATURE_AI_CALLS), 'error');
            return;
        }

        $language = Language::query()->find($this->selectedLanguageId);

        if (!$language) {
            return;
        }

        $language->forceFill([
            'translation_status' => 'queued',
            'translation_progress' => 0,
        ])->save();

        TranslateStoreJob::dispatch(tenant()->getTenantKey(), $language->id, triggeredBy: auth('tenant')->id());
        $limitService->incrementCounter(tenant(), 'ai_calls_count');

        $this->toast('Store translation queued. This may take a while.');
    }

    protected function aiTranslationAllowed(): bool
    {
        $limitService = app(PlanLimitService::class);

        if (!$limitService->aiTranslationEnabled(tenant())) {
            $this->toast($limitService->aiTranslationErrorMessage(), 'error');
            return false;
        }

        return true;
    }

    protected function pageData(): array
    {
        $languages = Language::query()->where('is_active', true)->orderBy('sort_order')->orderByDesc('is_default')->get();
        $service = app(TenantTranslationService::class);
        $selectedLanguage = Language::query()->find($this->selectedLanguageId);

        $rows = $selectedLanguage ? $service->keysForLocale($selectedLanguage->code) : [];

        $filtered = collect($rows)
            ->filter(function (array $row) {
                $matchesSearch = $this->search === ''
                    || str_contains(mb_strtolower($row['key']), mb_strtolower($this->search))
                    || str_contains(mb_strtolower((string) $row['value']), mb_strtolower($this->search));

                if (!$matchesSearch) {
                    return false;
                }

                if (!$this->showOnlyMissing) {
                    return true;
                }

                return $row['override'] === null;
            })
            ->values();

        $total = $filtered->count();
        $lastPage = max(1, (int) ceil($total / max(1, $this->perPage)));

        if ($this->page > $lastPage) {
            $this->page = $lastPage;
        }

        $paged = $filtered->slice(($this->page - 1) * $this->perPage, $this->perPage)->values()->all();

        return array_merge(parent::pageData(), [
            'languages' => $languages,
            'selectedLanguage' => $selectedLanguage,
            'rows' => $paged,
            'total' => $total,
            'page' => $this->page,
            'perPage' => $this->perPage,
            'lastPage' => $lastPage,
            'aiTranslationEnabled' => app(PlanLimitService::class)->aiTranslationEnabled(tenant()),
            'polling' => $selectedLanguage && in_array($selectedLanguage->translation_status, ['queued', 'running'], true),
        ]);
    }
}
