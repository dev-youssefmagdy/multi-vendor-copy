<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Settings;

use App\Exceptions\Tenant\PanelActionException;
use App\Http\Controllers\Tenant\Panel\Concerns\StashesInlinePaymentTokens;
use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Http\Requests\Tenant\Panel\Settings\PurchaseAiTranslationRequest;
use App\Models\Language as CentralLanguage;
use App\Models\Tenant\Language as TenantLanguage;
use App\Models\TenantAiTranslationPurchase;
use App\PaymentGateway\PaymentManager;
use App\Services\AiTranslationPurchaseService;
use App\Services\Tenant\PlanLimitService;
use App\Support\Tenant\Payments\InlineGatewayPresenter;
use App\Support\Tenant\TableColumn;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

final class AiTranslationController extends PanelController
{
    use StashesInlinePaymentTokens;

    public function __construct(
        private readonly AiTranslationPurchaseService $service,
        private readonly PlanLimitService $limitService,
        private readonly InlineGatewayPresenter $presenter,
    ) {
    }

    public function index(): View
    {
        $tenant = tenant();
        $canUseAi = $this->limitService->aiTranslationEnabled($tenant);

        $cards = $this->cards($canUseAi);

        $gateways = $canUseAi ? app(PaymentManager::class)->vendorPaymentGateways() : collect();

        return view('tenant.pages.settings.ai-translation.index', [
            'canUseAi' => $canUseAi,
            'cards' => $cards,
            'presented' => $this->presenter->present($gateways),
            'columns' => [
                TableColumn::make('language', 'Language')->orderable(false),
                TableColumn::make('date', 'Date')->orderable(false),
                TableColumn::make('amount', 'Amount')->orderable(false),
                TableColumn::make('status', 'Status')->orderable(false),
            ],
        ]);
    }

    public function historyData(): JsonResponse
    {
        $history = $this->service->history(tenant()->id);

        return DataTables::collection($history)
            ->addIndexColumn()
            ->editColumn('language', fn (TenantAiTranslationPurchase $run) => e($run->language->name ?? 'Unknown'))
            ->editColumn('date', fn (TenantAiTranslationPurchase $run) => $run->created_at->format('M d, Y H:i'))
            ->editColumn('amount', fn (TenantAiTranslationPurchase $run) => $run->amount > 0 ? '$' . number_format((float) $run->amount, 2) : 'Free')
            ->addColumn('status', fn (TenantAiTranslationPurchase $run) => view('tenant.pages.settings.ai-translation._cols.status', ['run' => $run])->render())
            ->rawColumns(['status'])
            ->toJson();
    }

    public function run(int $language): JsonResponse
    {
        $centralLanguage = CentralLanguage::query()->find($language);

        if (!$centralLanguage || !$this->service->isFree($centralLanguage) || !$this->service->canPurchase(tenant(), $centralLanguage)) {
            throw new PanelActionException('This language requires payment or is not enabled on your plan.', 422);
        }

        if (!$this->limitService->canPerform(tenant(), PlanLimitService::FEATURE_AI_CALLS)) {
            throw new PanelActionException($this->limitService->errorMessage(PlanLimitService::FEATURE_AI_CALLS), 422);
        }

        $this->service->completePurchase(tenant(), $centralLanguage, []);
        $this->limitService->incrementCounter(tenant(), 'ai_calls_count');

        return $this->success('AI translation started. This may take a few minutes.');
    }

    public function purchase(PurchaseAiTranslationRequest $request, int $language): JsonResponse
    {
        $validated = $request->validated();

        $centralLanguage = CentralLanguage::query()
            ->where('id', $language)
            ->whereNotNull('ai_translation_price')
            ->where('is_active', true)
            ->firstOrFail();

        if ((float) $centralLanguage->ai_translation_price <= 0) {
            throw new PanelActionException('This language is free — no payment required.', 422);
        }

        if (!$this->limitService->canPerform(tenant(), PlanLimitService::FEATURE_AI_CALLS)) {
            throw new PanelActionException($this->limitService->errorMessage(PlanLimitService::FEATURE_AI_CALLS), 422);
        }

        $this->stashInlineTokens($request, $validated['gateway']);

        session([
            'tenant_ai_translation_pending_payment' => [
                'language_id' => $centralLanguage->id,
                'gateway' => $validated['gateway'],
            ],
        ]);

        return $this->success('Redirecting to payment…', redirect: route('tenant.ai-translation-purchase.charge', [
            'gateway' => $validated['gateway'],
            'languageId' => $centralLanguage->id,
        ]));
    }

    public function validatePurchase(PurchaseAiTranslationRequest $request): JsonResponse
    {
        return $this->validFormResponse();
    }

    public function status(): JsonResponse
    {
        $tenant = tenant();
        $canUseAi = $this->limitService->aiTranslationEnabled($tenant);

        $cards = $this->cards($canUseAi);

        return response()->json([
            'cards' => $cards->map(fn (array $card) => [
                'id' => $card['id'],
                'translation_status' => $card['translation_status'],
                'translation_progress' => $card['translation_progress'],
                'translation_summary' => $card['translation_summary'],
                'last_status' => $card['last_status'],
                'last_run' => $card['last_run'],
            ])->values(),
        ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function cards(bool $canUseAi): Collection
    {
        $tenant = tenant();

        if (!$canUseAi) {
            return collect();
        }

        $languages = $this->service->availableLanguages($tenant);

        $tenantLanguages = TenantLanguage::query()
            ->whereNotNull('central_language_id')
            ->get()
            ->keyBy('central_language_id');

        $activeTenantCentralIds = $tenantLanguages
            ->filter(fn (TenantLanguage $lang) => $lang->is_active)
            ->keys()
            ->all();

        $history = $this->service->history($tenant->id);

        return $languages->map(function (CentralLanguage $lang) use ($activeTenantCentralIds, $history, $tenantLanguages) {
            $lastRun = $history->where('central_language_id', $lang->id)->sortByDesc('created_at')->first();
            $tenantLanguage = $tenantLanguages->get($lang->id);

            return [
                'id' => $lang->id,
                'name' => $lang->name,
                'native_name' => $lang->native_name,
                'code' => $lang->code,
                'is_free' => $lang->aiTranslationIsFree(),
                'price' => $lang->ai_translation_price,
                'is_active' => in_array($lang->id, $activeTenantCentralIds, true),
                'last_run' => $lastRun?->translated_at?->format('M d, Y H:i'),
                'last_status' => $lastRun?->status,
                'translation_status' => $tenantLanguage?->translation_status,
                'translation_progress' => $tenantLanguage?->translation_progress ?? 0,
                'translation_summary' => $tenantLanguage?->translation_summary,
            ];
        })->values();
    }
}
