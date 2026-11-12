<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Finance;

use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Models\TenantPayout;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Support\Tenant\Metric;
use App\Support\Tenant\TableColumn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

final class PayoutsController extends PanelController
{
    public function __construct(private readonly TenantPanelRepository $repo)
    {
    }

    public function index(Request $request): View
    {
        $stats = $this->repo->payoutStats();

        $statusOptions = ['' => __('All Statuses')] + collect($stats['statuses'])
            ->mapWithKeys(fn (string $status) => [$status => __(ucwords($status))])
            ->all();

        return view('tenant.pages.finance.payouts.index', [
            'stats' => Metric::cards([
                ['label' => __('Total Payouts'), 'value' => $stats['total'], 'format' => 'number', 'caption' => __('Payouts received from central.'), 'dot' => 'dot-cyan'],
                ['label' => __('Total Received'), 'value' => $stats['total_received'], 'format' => 'currency', 'caption' => __('Cumulative amount received from central.'), 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => __('This Month'), 'value' => $stats['this_month'], 'format' => 'currency', 'caption' => __('Payouts received this calendar month.'), 'dot' => 'dot-cyan'],
            ]),
            'statusOptions' => $statusOptions,
            'columns' => [
                TableColumn::make('invoice_number', __('Invoice'))->orderable(false),
                TableColumn::make('method', __('Method'))->orderable(false)->searchable(false),
                TableColumn::make('amount', __('Amount'))->orderable(false)->searchable(false),
                TableColumn::make('status', __('Status'))->orderable(false)->searchable(false),
                TableColumn::make('paid_at', __('Paid At'))->orderable(false)->searchable(false),
            ],
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $filters = $this->filters($request, ['search', 'status']);

        $query = $this->repo->queryPayouts($filters);

        return DataTables::eloquent($query)
            ->editColumn('invoice_number', fn (TenantPayout $p) => view('tenant.pages.finance.payouts._cols.invoice', ['record' => $p])->render())
            ->editColumn('method', fn (TenantPayout $p) => '<div class="entity-title">' . e(__(ucwords(str_replace('_', ' ', $p->method ?? '—')))) . '</div>')
            ->editColumn('amount', fn (TenantPayout $p) => '<strong>$' . number_format((float) $p->amount, 2) . '</strong>')
            ->editColumn('status', fn (TenantPayout $p) => view('tenant.pages.finance.payouts._cols.status', ['record' => $p])->render())
            ->editColumn('paid_at', fn (TenantPayout $p) => view('tenant.pages.finance.payouts._cols.paid-at', ['record' => $p])->render())
            ->rawColumns(['invoice_number', 'method', 'amount', 'status', 'paid_at'])
            ->toJson();
    }
}
