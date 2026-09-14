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

        $statusOptions = ['' => 'All Statuses'] + collect($stats['statuses'])
            ->mapWithKeys(fn (string $status) => [$status => ucwords($status)])
            ->all();

        return view('tenant.pages.finance.payouts.index', [
            'stats' => Metric::cards([
                ['label' => 'Total Payouts', 'value' => $stats['total'], 'format' => 'number', 'caption' => 'Payouts received from central.', 'dot' => 'dot-cyan'],
                ['label' => 'Total Received', 'value' => $stats['total_received'], 'format' => 'currency', 'caption' => 'Cumulative amount received from central.', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => 'This Month', 'value' => $stats['this_month'], 'format' => 'currency', 'caption' => 'Payouts received this calendar month.', 'dot' => 'dot-cyan'],
            ]),
            'statusOptions' => $statusOptions,
            'columns' => [
                TableColumn::make('invoice_number', 'Invoice')->orderable(false),
                TableColumn::make('method', 'Method')->orderable(false)->searchable(false),
                TableColumn::make('amount', 'Amount')->orderable(false)->searchable(false),
                TableColumn::make('status', 'Status')->orderable(false)->searchable(false),
                TableColumn::make('paid_at', 'Paid At')->orderable(false)->searchable(false),
            ],
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $filters = $this->filters($request, ['search', 'status']);

        $query = $this->repo->queryPayouts($filters);

        return DataTables::eloquent($query)
            ->editColumn('invoice_number', fn (TenantPayout $p) => view('tenant.pages.finance.payouts._cols.invoice', ['record' => $p])->render())
            ->editColumn('method', fn (TenantPayout $p) => '<div class="entity-title">' . e(ucwords(str_replace('_', ' ', $p->method ?? '—'))) . '</div>')
            ->editColumn('amount', fn (TenantPayout $p) => '<strong>$' . number_format((float) $p->amount, 2) . '</strong>')
            ->editColumn('status', fn (TenantPayout $p) => view('tenant.pages.finance.payouts._cols.status', ['record' => $p])->render())
            ->editColumn('paid_at', fn (TenantPayout $p) => view('tenant.pages.finance.payouts._cols.paid-at', ['record' => $p])->render())
            ->rawColumns(['invoice_number', 'method', 'amount', 'status', 'paid_at'])
            ->toJson();
    }
}
