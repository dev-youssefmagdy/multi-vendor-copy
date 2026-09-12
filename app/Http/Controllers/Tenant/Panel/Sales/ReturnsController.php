<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Sales;

use App\Enums\ReturnStatus;
use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Models\ReturnRequest;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Support\Tenant\Metric;
use App\Support\Tenant\TableColumn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

final class ReturnsController extends PanelController
{
    public function __construct(private readonly TenantPanelRepository $repo)
    {
    }

    public function index(Request $request): View
    {
        $stats = $this->repo->returnStats();

        $statusOptions = [];
        foreach (ReturnStatus::cases() as $case) {
            $statusOptions[$case->value] = $case->label();
        }

        return view('tenant.pages.sales.returns.index', [
            'stats' => Metric::cards([
                ['label' => 'Total Returns', 'value' => $stats['total'], 'format' => 'number', 'caption' => 'All return requests', 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
                ['label' => 'Pending Review', 'value' => $stats['pending'], 'format' => 'number', 'caption' => 'Awaiting your decision', 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
                ['label' => 'Approved', 'value' => $stats['approved'], 'format' => 'number', 'caption' => 'Returns accepted', 'dot' => 'dot-blue', 'glow' => 'card-glow-blue'],
                ['label' => 'Refunded', 'value' => $stats['refunded'], 'format' => 'number', 'caption' => 'Amount returned', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
            ]),
            'statusOptions' => $statusOptions,
            'columns' => [
                TableColumn::make('order_number', 'Order')->orderable(false),
                TableColumn::make('status', 'Status')->orderable(false),
                TableColumn::make('reason', 'Reason')->orderable(false),
                TableColumn::make('refund', 'Refund')->orderable(false),
                TableColumn::make('date', 'Date')->orderable(false),
                TableColumn::actions(),
            ],
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $filters = $this->filters($request, ['search', 'status']);

        $query = $this->repo->queryReturns($filters);

        return DataTables::eloquent($query)
            ->editColumn('order_number', fn (ReturnRequest $r) => view('tenant.pages.sales.returns._cols.order', ['record' => $r])->render())
            ->editColumn('status', fn (ReturnRequest $r) => view('tenant.pages.sales.returns._cols.status', ['record' => $r])->render())
            ->editColumn('reason', fn (ReturnRequest $r) => '<div class="entity-subtitle">' . e($r->reason->label()) . '</div>')
            ->editColumn('refund', fn (ReturnRequest $r) => $r->refund_amount
                ? '<div class="entity-title">$' . number_format((float) $r->refund_amount, 2) . '</div>'
                : '<span class="entity-subtitle">—</span>')
            ->editColumn('date', fn (ReturnRequest $r) => '<div class="entity-subtitle">' . $r->created_at?->format('M d, Y') . '</div>')
            ->addColumn('actions', fn (ReturnRequest $r) => view('tenant.pages.sales.returns._cols.actions', ['record' => $r])->render())
            ->rawColumns(['order_number', 'status', 'reason', 'refund', 'date', 'actions'])
            ->toJson();
    }
}
