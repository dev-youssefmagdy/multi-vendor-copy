<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Settings;

use App\Exceptions\Tenant\PanelActionException;
use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Http\Requests\Tenant\Panel\Settings\AiTranslateKeyRequest;
use App\Http\Requests\Tenant\Panel\Settings\AiTranslateKeysRequest;
use App\Http\Requests\Tenant\Panel\Settings\SaveTranslationKeyRequest;
use App\Jobs\Tenant\TranslateStoreJob;
use App\Models\Tenant\Language;
use App\Services\OpenAiTranslationService;
use App\Services\Tenant\PlanLimitService;
use App\Services\Tenant\TenantTranslationService;
use App\Support\Tenant\TableColumn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;
use Yajra\DataTables\Facades\DataTables;

final class TranslationsController extends PanelController
{
    public function __construct(
        private readonly TenantTranslationService $service,
        private readonly PlanLimitService $limitService,
    ) {
    }

    public function index(): View
    {
        $languages = Language::query()->where('is_active', true)->orderBy('sort_order')->orderByDesc('is_default')->get();

        $selectedLanguageId = Language::query()->where('is_default', true)->value('id')
            ?? Language::query()->where('is_active', true)->value('id');

        return view('tenant.pages.settings.translations.index', [
            'languages' => $languages,
            'selectedLanguageId' => $selectedLanguageId,
            'aiTranslationEnabled' => $this->limitService->aiTranslationEnabled(tenant()),
            'columns' => [
                TableColumn::select(),
                TableColumn::make('key', 'Key')->orderable(false),
                TableColumn::make('default', 'Default text')->orderable(false),
                TableColumn::make('translation', 'Translation')->orderable(false),
                TableColumn::actions('Actions'),
            ],
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $filters = $this->filters($request, ['language_id', 'search', 'only_missing']);
        $languageId = $filters['language_id'] ?? null;

        $language = $languageId ? Language::query()->find($languageId) : null;

        if (!$language) {
            return DataTables::collection(collect())->toJson();
        }

        $search = (string) ($filters['search'] ?? '');
        $onlyMissing = filter_var($filters['only_missing'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $rows = collect($this->service->keysForLocale($language->code))
            ->filter(function (array $row) use ($search, $onlyMissing) {
                $matchesSearch = $search === ''
                    || str_contains(mb_strtolower($row['key']), mb_strtolower($search))
                    || str_contains(mb_strtolower((string) $row['value']), mb_strtolower($search));

                if (!$matchesSearch) {
                    return false;
                }

                if (!$onlyMissing) {
                    return true;
                }

                return $row['override'] === null;
            })
            ->map(fn (array $row) => $row + ['id' => $row['key']])
            ->values();

        return DataTables::collection($rows)
            ->addIndexColumn()
            ->addColumn('select', fn (array $row) => view('tenant.pages.settings.translations._cols.select', ['row' => $row])->render())
            ->editColumn('key', fn (array $row) => view('tenant.pages.settings.translations._cols.key', ['row' => $row])->render())
            ->editColumn('default', fn (array $row) => e(\Illuminate\Support\Str::limit((string) $row['default'], 80)))
            ->editColumn('translation', fn (array $row) => view('tenant.pages.settings.translations._cols.translation', ['row' => $row, 'language' => $language])->render())
            ->addColumn('actions', fn (array $row) => view('tenant.pages.settings.translations._cols.actions', [
                'row' => $row,
                'language' => $language,
                'aiTranslationEnabled' => $this->limitService->aiTranslationEnabled(tenant()),
            ])->render())
            ->rawColumns(['select', 'key', 'translation', 'actions'])
            ->toJson();
    }

    public function saveKey(SaveTranslationKeyRequest $request, Language $language): JsonResponse
    {
        $validated = $request->validated();

        try {
            $this->service->saveOverride((string) $language->id, $validated['key'], (string) ($validated['value'] ?? ''));
        } catch (RuntimeException $e) {
            throw new PanelActionException($e->getMessage(), 422);
        }

        return $this->success('Translation saved successfully.');
    }

    public function translateKeyWithAi(AiTranslateKeyRequest $request, Language $language, OpenAiTranslationService $ai): JsonResponse
    {
        $this->assertAiAllowed();

        if (!$this->limitService->canPerform(tenant(), PlanLimitService::FEATURE_AI_CALLS)) {
            throw new PanelActionException($this->limitService->errorMessage(PlanLimitService::FEATURE_AI_CALLS), 422);
        }

        try {
            $value = $this->service->translateKeyWithAi($language, $request->validated()['key'], $ai);
        } catch (RuntimeException $e) {
            throw new PanelActionException($e->getMessage(), 422);
        }

        $this->limitService->incrementCounter(tenant(), 'ai_calls_count');

        return $this->success('Key translated with AI successfully.', ['value' => $value]);
    }

    public function translateSelectedWithAi(AiTranslateKeysRequest $request, Language $language, OpenAiTranslationService $ai): JsonResponse
    {
        $this->assertAiAllowed();

        $keys = $request->validated()['keys'] ?? [];

        if ($keys === []) {
            throw new PanelActionException('Select at least one key to translate.', 422);
        }

        if (!$this->limitService->canPerform(tenant(), PlanLimitService::FEATURE_AI_CALLS)) {
            throw new PanelActionException($this->limitService->errorMessage(PlanLimitService::FEATURE_AI_CALLS), 422);
        }

        $count = $this->service->translateKeysWithAi($language, $keys, $ai);
        $this->limitService->incrementCounter(tenant(), 'ai_calls_count');

        return $this->success("{$count} key(s) translated with AI successfully.");
    }

    public function translateStore(Language $language): JsonResponse
    {
        $this->assertAiAllowed();

        if (!$this->limitService->canPerform(tenant(), PlanLimitService::FEATURE_AI_CALLS)) {
            throw new PanelActionException($this->limitService->errorMessage(PlanLimitService::FEATURE_AI_CALLS), 422);
        }

        $language->forceFill([
            'translation_status' => 'queued',
            'translation_progress' => 0,
        ])->save();

        TranslateStoreJob::dispatch(tenant()->getTenantKey(), $language->id, triggeredBy: auth('tenant')->id());
        $this->limitService->incrementCounter(tenant(), 'ai_calls_count');

        return $this->success('Store translation queued. This may take a while.');
    }

    public function status(Language $language): JsonResponse
    {
        $language->refresh();

        $summary = json_decode((string) $language->translation_summary, true);

        return response()->json([
            'translation_status' => $language->translation_status,
            'translation_progress' => $language->translation_progress,
            'items_translated' => $summary['items_translated'] ?? 0,
        ]);
    }

    private function assertAiAllowed(): void
    {
        if (!$this->limitService->aiTranslationEnabled(tenant())) {
            throw new PanelActionException($this->limitService->aiTranslationErrorMessage(), 422);
        }
    }
}
