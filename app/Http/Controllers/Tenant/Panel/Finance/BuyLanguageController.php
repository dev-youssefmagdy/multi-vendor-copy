<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Finance;

use App\Exceptions\Tenant\PanelActionException;
use App\Http\Controllers\Tenant\Panel\Concerns\StashesInlinePaymentTokens;
use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Http\Requests\Tenant\Panel\Finance\BuyLanguageRequest;
use App\Models\Language as CentralLanguage;
use App\PaymentGateway\PaymentManager;
use App\Services\LanguagePurchaseService;
use App\Support\Tenant\Payments\InlineGatewayPresenter;
use App\Support\Tenant\TableColumn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

final class BuyLanguageController extends PanelController
{
    use StashesInlinePaymentTokens;

    public function __construct(
        private readonly LanguagePurchaseService $service,
        private readonly InlineGatewayPresenter $presenter,
    ) {
    }

    public function index(Request $request): View
    {
        $gateways = app(PaymentManager::class)->vendorPaymentGateways();

        return view('tenant.pages.finance.buy-languages.index', [
            'presented' => $this->presenter->present($gateways),
            'columns' => [
                TableColumn::make('language', 'Language')->orderable(false),
                TableColumn::make('direction', 'Direction')->orderable(false),
                TableColumn::make('price', 'Price')->orderable(false),
                TableColumn::actions('Action'),
            ],
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $languages = $this->service->availableForPurchase(tenant());

        return DataTables::collection($languages)
            ->addIndexColumn()
            ->editColumn('language', fn (CentralLanguage $language) => view('tenant.pages.finance.buy-languages._cols.language', ['language' => $language])->render())
            ->editColumn('direction', fn (CentralLanguage $language) => strtoupper($language->direction->value))
            ->editColumn('price', fn (CentralLanguage $language) => '$' . number_format((float) $language->price, 2))
            ->addColumn('actions', fn (CentralLanguage $language) => view('tenant.pages.finance.buy-languages._cols.actions', ['language' => $language])->render())
            ->rawColumns(['language', 'actions'])
            ->toJson();
    }

    public function purchase(BuyLanguageRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $language = CentralLanguage::query()
            ->where('id', $validated['language_id'])
            ->where('is_free', false)
            ->where('is_active', true)
            ->firstOrFail();

        $tenant = tenant();

        if ($this->service->tenantHasPurchased($tenant, $language->id)) {
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

    public function validatePurchase(BuyLanguageRequest $request): JsonResponse
    {
        return $this->validFormResponse();
    }
}
