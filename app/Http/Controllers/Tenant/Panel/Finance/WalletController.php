<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Finance;

use App\Enums\PackageStatus;
use App\Http\Controllers\Tenant\Panel\Concerns\StashesInlinePaymentTokens;
use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Http\Requests\Tenant\Panel\Finance\SubscribeRequest;
use App\Models\Package;
use App\Models\Tenant\Subscription;
use App\Models\Tenant\Transaction;
use App\PaymentGateway\PaymentManager;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Support\Tenant\Metric;
use App\Support\Tenant\Payments\InlineGatewayPresenter;
use App\Support\Tenant\TableColumn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

final class WalletController extends PanelController
{
    use StashesInlinePaymentTokens;

    public function __construct(
        private readonly TenantPanelRepository $repo,
        private readonly InlineGatewayPresenter $presenter,
    ) {
    }

    public function index(Request $request): View
    {
        $overview = $this->repo->walletOverview();
        $tenant = tenant();
        $currentPackage = $tenant->package;

        $packages = Package::query()
            ->where('status', PackageStatus::Published->value)
            ->orderBy('price')
            ->get();

        $gateways = app(PaymentManager::class)->vendorPaymentGateways();

        return view('tenant.pages.finance.wallet.index', [
            'cards' => Metric::cards($overview['cards']),
            'currentPackage' => $currentPackage,
            'packages' => $packages,
            'presented' => $this->presenter->present($gateways),
            'subscriptionColumns' => [
                TableColumn::make('subscription', 'Subscription')->orderable(false),
                TableColumn::make('status', 'Status')->orderable(false),
                TableColumn::make('term', 'Term')->orderable(false),
                TableColumn::make('gateway', 'Gateway')->orderable(false),
                TableColumn::make('price', 'Price')->orderable(false),
                TableColumn::make('window', 'Window')->orderable(false),
                TableColumn::make('transaction', 'Transaction')->orderable(false),
            ],
            'transactionColumns' => [
                TableColumn::make('uuid', 'Reference')->orderable(false),
                TableColumn::make('type', 'Direction')->orderable(false),
                TableColumn::make('amount', 'Amount')->orderable(false),
                TableColumn::make('admin_profit', 'Admin Profit')->orderable(false),
                TableColumn::make('model', 'Linked Model')->orderable(false),
                TableColumn::make('details', 'Details')->orderable(false),
                TableColumn::make('created_at', 'Created At')->orderable(false),
            ],
        ]);
    }

    public function subscriptionsData(Request $request): JsonResponse
    {
        return DataTables::eloquent($this->repo->querySubscriptions())
            ->addIndexColumn()
            ->editColumn('subscription', fn (Subscription $s) => view('tenant.pages.finance.wallet._cols.subscription', ['subscription' => $s])->render())
            ->editColumn('status', fn (Subscription $s) => view('tenant.pages.finance.wallet._cols.status', ['subscription' => $s])->render())
            ->editColumn('term', fn (Subscription $s) => str($s->term->value)->headline()->toString())
            ->editColumn('gateway', fn (Subscription $s) => view('tenant.pages.finance.wallet._cols.gateway', ['subscription' => $s])->render())
            ->editColumn('price', fn (Subscription $s) => '$' . number_format((float) $s->price, 2))
            ->editColumn('window', fn (Subscription $s) => (optional($s->start_date)->format('M d, Y') ?: '-') . ' - ' . (optional($s->end_date)->format('M d, Y') ?: '-'))
            ->editColumn('transaction', fn (Subscription $s) => view('tenant.pages.finance.wallet._cols.transaction', ['subscription' => $s])->render())
            ->rawColumns(['subscription', 'status', 'gateway', 'transaction'])
            ->toJson();
    }

    public function transactionsData(Request $request): JsonResponse
    {
        return DataTables::eloquent($this->repo->queryTransactions())
            ->addIndexColumn()
            ->editColumn('uuid', fn (Transaction $t) => e($t->uuid))
            ->editColumn('type', fn (Transaction $t) => view('tenant.pages.finance.wallet._cols.direction', ['transaction' => $t])->render())
            ->editColumn('amount', fn (Transaction $t) => '$' . number_format((float) $t->amount, 2))
            ->editColumn('admin_profit', fn (Transaction $t) => '$' . number_format((float) ($t->admin_profit ?? 0), 2))
            ->editColumn('model', fn (Transaction $t) => view('tenant.pages.finance.wallet._cols.linked-model', ['transaction' => $t])->render())
            ->addColumn('details', fn (Transaction $t) => view('tenant.pages.finance.wallet._cols.details', ['transaction' => $t])->render())
            ->editColumn('created_at', fn (Transaction $t) => $t->created_at?->format('M d, Y H:i'))
            ->rawColumns(['type', 'model', 'details'])
            ->toJson();
    }

    public function subscribe(SubscribeRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $package = Package::query()
            ->where('id', $validated['package_id'])
            ->where('status', PackageStatus::Published->value)
            ->firstOrFail();

        $tenant = tenant();
        $type = ((int) $tenant->package_id === $package->id) ? 'renewal' : 'upgrade';

        $this->stashInlineTokens($request, $validated['gateway']);

        session([
            'tenant_subscription_pending_payment' => [
                'package_id' => $package->id,
                'gateway' => $validated['gateway'],
                'type' => $type,
            ],
        ]);

        return $this->success('Redirecting to payment…', redirect: route('tenant.subscription-payment.charge', [
            'gateway' => $validated['gateway'],
            'packageId' => $package->id,
            'type' => $type,
        ]));
    }

    public function validateSubscribe(SubscribeRequest $request): JsonResponse
    {
        return $this->validFormResponse();
    }
}
