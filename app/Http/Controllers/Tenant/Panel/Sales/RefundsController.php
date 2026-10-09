<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Sales;

use App\Enums\RefundSource;
use App\Enums\RefundStatus;
use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Models\Refund;
use App\Models\Tenant\Order;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Support\Tenant\Metric;
use App\Support\Tenant\TableColumn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

/**
 * Sales → Refunds (RETURN_EXCHANGE_REFUND_PLAN.md B.8 Vendor): every refund of the current tenant
 * (cancellation, return and manual), with status / source filters and stat cards. Single-refund
 * actions live in RefundController and on the order / return pages.
 */
final class RefundsController extends PanelController
{
    public function __construct(private readonly TenantPanelRepository $repo) {}

    public function index(Request $request): View
    {
        $stats = $this->repo->refundStats();

        return view('tenant.pages.sales.refunds.index', [
            'stats' => Metric::cards([
                ['label' => __('Total Refunded'), 'value' => $stats['refunded_amount'], 'format' => 'currency', 'caption' => __('Money returned to customers'), 'dot' => 'dot-violet', 'glow' => 'card-glow-violet'],
                ['label' => __('Pending'), 'value' => $stats['pending'], 'format' => 'number', 'caption' => __('Waiting to be processed'), 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
                ['label' => __('Failed'), 'value' => $stats['failed'], 'format' => 'number', 'caption' => __('Need a retry or manual completion'), 'dot' => 'dot-red', 'glow' => 'card-glow-red'],
                ['label' => __('Completed'), 'value' => $stats['completed'], 'format' => 'number', 'caption' => __('Refunds completed'), 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
            ]),
            'statusOptions' => collect(RefundStatus::cases())->mapWithKeys(fn (RefundStatus $status) => [$status->value => $status->label()])->all(),
            'sourceOptions' => collect(RefundSource::cases())->mapWithKeys(fn (RefundSource $source) => [$source->value => $source->label()])->all(),
            'columns' => [
                TableColumn::make('reference', __('Reference'))->orderable(false),
                TableColumn::make('order', __('Order'))->orderable(false)->searchable(false),
                TableColumn::make('source', __('Source'))->orderable(false),
                TableColumn::make('amount', __('Amount'))->orderable(false),
                TableColumn::make('method', __('Method'))->orderable(false)->searchable(false),
                TableColumn::make('status', __('Status'))->orderable(false),
                TableColumn::make('requested', __('Requested'))->orderable(false)->searchable(false),
                TableColumn::make('processed', __('Processed'))->orderable(false)->searchable(false),
            ],
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $filters = $this->filters($request, ['search', 'status', 'source']);

        $payload = DataTables::eloquent($this->repo->queryRefunds($filters))
            ->editColumn('reference', fn (Refund $r) => view('tenant.pages.sales.refunds._cols.reference', ['refund' => $r])->render())
            ->addColumn('order', fn (Refund $r) => e((string) $r->order_number))
            ->editColumn('source', fn (Refund $r) => view('tenant.pages.sales.refunds._cols.source', ['refund' => $r])->render())
            ->editColumn('amount', fn (Refund $r) => view('tenant.pages.sales.refunds._cols.amount', ['refund' => $r])->render())
            ->addColumn('method', fn (Refund $r) => view('tenant.pages.sales.refunds._cols.method', ['refund' => $r])->render())
            ->editColumn('status', fn (Refund $r) => view('tenant::components.status-badge', ['status' => $r->status])->render())
            ->addColumn('requested', fn (Refund $r) => '<div class="entity-subtitle">'.e((string) $r->requested_at?->format('M d, Y H:i')).'</div>')
            ->addColumn('processed', fn (Refund $r) => $r->processed_at
                ? '<div class="entity-subtitle">'.e($r->processed_at->format('M d, Y H:i')).'</div>'
                : '<span class="entity-subtitle">—</span>')
            ->rawColumns(['reference', 'order', 'source', 'amount', 'method', 'status', 'requested', 'processed'])
            // Never ship the raw gateway payload (meta) or internal notes to the browser.
            ->only(['id', 'order_number', 'reference', 'order', 'source', 'amount', 'method', 'status', 'requested', 'processed'])
            ->toArray();

        // Refunds are central rows; the order link needs the tenant order id — one lookup per page.
        $orderIds = Order::query()
            ->whereIn('uuid', collect($payload['data'] ?? [])->pluck('order_number')->filter()->unique()->values())
            ->pluck('id', 'uuid');

        $payload['data'] = collect($payload['data'] ?? [])->map(function (array $row) use ($orderIds) {
            $row['order'] = view('tenant.pages.sales.refunds._cols.order', [
                'orderNumber' => (string) ($row['order_number'] ?? ''),
                'orderId' => $orderIds[$row['order_number'] ?? ''] ?? null,
            ])->render();

            return $row;
        })->all();

        return response()->json($payload);
    }
}
