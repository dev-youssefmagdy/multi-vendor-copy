<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Catalog;

use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Models\ProductEditRequest;
use App\Support\Tenant\Metric;
use App\Support\Tenant\TableColumn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

final class EditRequestsController extends PanelController
{
    public function index(): View
    {
        $tenantId = tenant()->getTenantKey();

        $counts = tenancy()->central(fn () => [
            'pending' => ProductEditRequest::forTenant($tenantId)->where('status', 'pending')->count(),
            'approved' => ProductEditRequest::forTenant($tenantId)->where('status', 'approved')->count(),
            'rejected' => ProductEditRequest::forTenant($tenantId)->where('status', 'rejected')->count(),
        ]);

        return view('tenant.pages.catalog.edit-requests.index', [
            'stats' => Metric::cards([
                ['label' => 'Pending', 'value' => $counts['pending'], 'format' => 'number', 'dot' => 'dot-amber'],
                ['label' => 'Approved', 'value' => $counts['approved'], 'format' => 'number', 'dot' => 'dot-green'],
                ['label' => 'Rejected', 'value' => $counts['rejected'], 'format' => 'number', 'dot' => 'dot-red'],
            ]),
            'columns' => [
                TableColumn::index(),
                TableColumn::make('product', 'Product')->orderable(false),
                TableColumn::make('changes', 'Requested Changes')->orderable(false),
                TableColumn::make('status', 'Status')->orderable(false),
                TableColumn::make('created_at', 'Requested At'),
                TableColumn::actions(),
            ],
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $filters = $this->filters($request, ['status']);
        $tenantId = tenant()->getTenantKey();

        $requests = tenancy()->central(fn () => ProductEditRequest::forTenant($tenantId)
            ->when(($filters['status'] ?? 'all') !== 'all', fn ($q) => $q->where('status', $filters['status']))
            ->latest()
            ->get());

        return DataTables::collection($requests)
            ->addIndexColumn()
            ->editColumn('product', fn (ProductEditRequest $r) => e($r->product_slug ?: 'Product #'.$r->product_id))
            ->editColumn('changes', fn (ProductEditRequest $r) => view('tenant.pages.catalog.edit-requests._cols.changes', ['request' => $r])->render())
            ->editColumn('status', fn (ProductEditRequest $r) => view('tenant.pages.catalog.edit-requests._cols.status', ['request' => $r])->render())
            ->editColumn('created_at', fn (ProductEditRequest $r) => $r->created_at?->diffForHumans())
            ->addColumn('actions', fn (ProductEditRequest $r) => $r->status->value === 'rejected'
                ? '<a href="'.route('tenant.products.edit', $r->product_id).'" class="btn btn-secondary btn-sm">Edit Again</a>'
                : '')
            ->rawColumns(['changes', 'status', 'actions'])
            ->toJson();
    }
}
