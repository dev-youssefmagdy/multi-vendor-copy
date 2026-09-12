<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Settings;

use App\Exceptions\Tenant\PanelActionException;
use App\Http\Controllers\Tenant\Panel\Concerns\StashesInlinePaymentTokens;
use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Http\Requests\Tenant\Panel\Settings\PurchaseLanguageRequest;
use App\Models\Language as CentralLanguage;
use App\Models\Tenant\Language;
use App\Models\Tenant\LanguagePurchase;
use App\PaymentGateway\PaymentManager;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Services\LanguagePurchaseService;
use App\Services\Tenant\PlanLimitService;
use App\Services\Tenant\TenantPanelService;
use App\Support\Tenant\Payments\InlineGatewayPresenter;
use App\Support\Tenant\TableColumn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

final class LanguagesManageController extends PanelController
{
    use StashesInlinePaymentTokens;

    public function __construct(
        private readonly TenantPanelRepository $repo,
        private readonly LanguagePurchaseService $purchaseService,
        private readonly InlineGatewayPresenter $presenter,
    ) {
    }

    public function index(): View
    {
        $gateways = app(PaymentManager::class)->vendorPaymentGateways();

        return view('tenant.pages.settings.languages-manage.index', [
            'presented' => $this->presenter->present($gateways),
            'installedColumns' => [
                TableColumn::make('language', 'Language')->orderable(false),
                TableColumn::make('direction', 'Direction')->orderable(false),
                TableColumn::make('countries', 'Countries')->orderable(false),
                TableColumn::make('type', 'Type')->orderable(false),
                TableColumn::make('status', 'Status')->orderable(false),
                TableColumn::actions(),
            ],
            'availableColumns' => [
                TableColumn::make('language', 'Language')->orderable(false),
                TableColumn::make('direction', 'Direction')->orderable(false),
                TableColumn::make('countries', 'Countries')->orderable(false),
                TableColumn::make('price', 'Price')->orderable(false),
                TableColumn::actions('Action'),
            ],
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $languages = $this->repo->languages();
        $purchasedIds = LanguagePurchase::query()->pluck('central_language_id')->all();

        $centralLanguages = CentralLanguage::query()
            ->whereIn('id', $languages->pluck('central_language_id')->filter()->unique()->values()->all())
            ->get(['id', 'is_free', 'countries'])
            ->keyBy('id');

        return DataTables::collection($languages)
            ->addIndexColumn()
            ->editColumn('language', fn (Language $language) => view('tenant.pages.settings.languages-manage._cols.language', ['language' => $language])->render())
            ->editColumn('direction', fn (Language $language) => strtoupper($language->direction->value))
            ->editColumn('countries', fn (Language $language) => view('tenant.pages.settings.languages-manage._cols.countries', [
                'countries' => $centralLanguages->get($language->central_language_id)?->countries ?? [],
            ])->render())
            ->editColumn('type', function (Language $language) use ($purchasedIds, $centralLanguages) {
                $isPurchased = $language->central_language_id && in_array($language->central_language_id, $purchasedIds, true);
                $isFree = !$language->central_language_id || !$isPurchased
                    ? ($centralLanguages->get($language->central_language_id)?->is_free ?? true)
                    : false;

                return $isFree
                    ? '<span class="badge badge-green">Free</span>'
                    : '<span class="badge badge-violet">Purchased</span>';
            })
            ->editColumn('status', fn (Language $language) => $language->is_default
                ? '<span class="badge badge-green">Default</span>'
                : ($language->is_active ? '<span class="badge badge-cyan">Active</span>' : '<span class="badge badge-amber">Inactive</span>'))
            ->addColumn('actions', fn (Language $language) => view('tenant.pages.settings.languages-manage._cols.actions', ['language' => $language])->render())
            ->rawColumns(['language', 'countries', 'type', 'status', 'actions'])
            ->toJson();
    }

    public function available(Request $request): JsonResponse
    {
        $languages = $this->purchaseService->availableForPurchase(tenant());

        return DataTables::collection($languages)
            ->addIndexColumn()
            ->editColumn('language', fn (CentralLanguage $language) => view('tenant.pages.settings.languages-manage._cols.available-language', ['language' => $language])->render())
            ->editColumn('direction', fn (CentralLanguage $language) => strtoupper($language->direction->value))
            ->editColumn('countries', fn (CentralLanguage $language) => view('tenant.pages.settings.languages-manage._cols.countries', [
                'countries' => $language->countries ?? [],
            ])->render())
            ->editColumn('price', fn (CentralLanguage $language) => '$' . number_format((float) $language->price, 2))
            ->addColumn('actions', fn (CentralLanguage $language) => view('tenant.pages.settings.languages-manage._cols.buy-action', ['language' => $language])->render())
            ->rawColumns(['language', 'countries', 'actions'])
            ->toJson();
    }

    public function toggleActive(Language $language, PlanLimitService $limitService): JsonResponse
    {
        $activating = !$language->is_active;

        if ($activating) {
            $isPurchased = $language->central_language_id
                && LanguagePurchase::query()->where('central_language_id', $language->central_language_id)->exists();

            if (!$isPurchased && !$limitService->canPerform(tenant(), PlanLimitService::FEATURE_LANGUAGES)) {
                throw new PanelActionException($limitService->errorMessage(PlanLimitService::FEATURE_LANGUAGES));
            }
        }

        $language->update(['is_active' => $activating]);

        return $this->success(__('Language state updated successfully.'));
    }

    public function makeDefault(Language $language, TenantPanelService $service): JsonResponse
    {
        $service->markDefaultLanguage($language);

        return $this->success(__('Default language updated successfully.'));
    }

    public function purchase(PurchaseLanguageRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $language = CentralLanguage::query()
            ->where('id', $validated['language_id'])
            ->where('is_free', false)
            ->where('is_active', true)
            ->firstOrFail();

        $tenant = tenant();

        if ($this->purchaseService->tenantHasPurchased($tenant, $language->id)) {
            throw new PanelActionException('You have already purchased this language.', 422);
        }

        $this->stashInlineTokens($request, $validated['gateway']);

        session([
            'tenant_language_pending_payment' => [
                'language_id' => $language->id,
                'gateway' => $validated['gateway'],
            ],
        ]);

        return $this->success('Redirecting to payment…', redirect: route('tenant.language-purchase.charge', [
            'gateway' => $validated['gateway'],
            'languageId' => $language->id,
        ]));
    }

    public function validatePurchase(PurchaseLanguageRequest $request): JsonResponse
    {
        return $this->validFormResponse();
    }
}
