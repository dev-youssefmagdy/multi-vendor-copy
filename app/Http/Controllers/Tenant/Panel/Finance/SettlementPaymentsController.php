<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Finance;

use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Models\VendorSettlement;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Support\Tenant\Metric;
use App\Support\Tenant\TableColumn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

final class SettlementPaymentsController extends PanelController
{
    public function __construct(private readonly TenantPanelRepository $repo)
    {
    }

    public function index(Request $request): View
    {
        $stats = $this->repo->settlementPaymentStats();

        $statusOptions = ['' => 'All Statuses'] + collect($stats['statuses'])
            ->mapWithKeys(fn (string $status) => [$status => ucwords(str_replace('_', ' ', $status))])
            ->all();

        return view('tenant.pages.finance.settlement-payments.index', [
            'stats' => Metric::cards([
                ['label' => 'Total Settlements', 'value' => $stats['total'], 'format' => 'number', 'caption' => 'Settlement payments made to central.', 'dot' => 'dot-cyan'],
                ['label' => 'Total Paid', 'value' => $stats['total_paid'], 'format' => 'currency', 'caption' => 'Cumulative amount settled with central.', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => 'This Month', 'value' => $stats['this_month'], 'format' => 'currency', 'caption' => 'Settlements paid this calendar month.', 'dot' => 'dot-cyan'],
            ]),
            'statusOptions' => $statusOptions,
            'columns' => [
                TableColumn::make('invoice_number', 'Invoice')->orderable(false),
                TableColumn::make('order', 'Order')->orderable(false)->searchable(false),
                TableColumn::make('gateway_code', 'Gateway')->orderable(false),
                TableColumn::make('transaction_id', 'Transaction')->orderable(false),
                TableColumn::make('amount', 'Amount')->orderable(false)->searchable(false),
                TableColumn::make('settled_at', 'Settled At')->orderable(false)->searchable(false),
            ],
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $filters = $this->filters($request, ['search', 'status']);
        $filters['status'] = $filters['status'] ?: 'paid';

        $query = $this->repo->querySettlementPayments($filters);

        return DataTables::eloquent($query)
            ->editColumn('invoice_number', fn (VendorSettlement $s) => view('tenant.pages.finance.settlement-payments._cols.invoice', ['record' => $s])->render())
            ->editColumn('order', fn (VendorSettlement $s) => view('tenant.pages.finance.settlement-payments._cols.order', ['record' => $s])->render())
            ->editColumn('gateway_code', fn (VendorSettlement $s) => '<div class="entity-title">' . e(ucwords(str_replace('_', ' ', $s->gateway_code ?? '—'))) . '</div>')
            ->editColumn('transaction_id', fn (VendorSettlement $s) => view('tenant.pages.finance.settlement-payments._cols.transaction', ['record' => $s])->render())
            ->editColumn('amount', fn (VendorSettlement $s) => view('tenant.pages.finance.settlement-payments._cols.amount', ['record' => $s])->render())
            ->editColumn('settled_at', fn (VendorSettlement $s) => view('tenant.pages.finance.settlement-payments._cols.settled-at', ['record' => $s])->render())
            ->rawColumns(['invoice_number', 'order', 'gateway_code', 'transaction_id', 'amount', 'settled_at'])
            ->toJson();
    }
}
