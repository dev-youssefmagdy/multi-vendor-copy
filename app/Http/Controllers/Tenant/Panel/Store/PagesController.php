<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Store;

use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Models\Tenant\Page;
use App\Models\Tenant\PaymentGateway;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Services\Tenant\TenantPanelService;
use App\Support\Tenant\Metric;
use App\Support\Tenant\TableColumn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

final class PagesController extends PanelController
{
    public function __construct(
        private readonly TenantPanelRepository $repo,
        private readonly TenantPanelService $service,
    ) {}

    public static function ensurePaymentGatewayActive(): ?RedirectResponse
    {
        if (PaymentGateway::query()->where('is_active', true)->exists()) {
            return null;
        }

        session()->flash('status', __('Please activate at least one payment gateway before managing store pages.'));
        session()->flash('status_type', 'warning');

        return redirect()->route('tenant.settings.payment-gateways');
    }

    public function index(Request $request): View|RedirectResponse
    {
        if ($redirect = self::ensurePaymentGatewayActive()) {
            return $redirect;
        }

        $stats = $this->repo->pageStats();

        return view('tenant.pages.store.pages.index', [
            'stats' => Metric::cards([
                ['label' => 'Pages', 'value' => $stats['total'], 'format' => 'number', 'caption' => 'Tenant storefront pages', 'dot' => 'dot-cyan'],
                ['label' => 'Active', 'value' => $stats['active'], 'format' => 'number', 'caption' => 'Visible published pages', 'dot' => 'dot-green'],
                ['label' => 'Draft', 'value' => $stats['draft'], 'format' => 'number', 'caption' => 'Pages hidden from storefront', 'dot' => 'dot-amber'],
            ]),
            'columns' => [
                TableColumn::index(),
                TableColumn::make('page', 'Page')->orderable(false),
                TableColumn::make('slug', 'Slug'),
                TableColumn::make('status', 'Status')->orderable(false),
                TableColumn::make('updated_at', 'Updated At'),
                TableColumn::actions(),
            ],
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $filters = $this->filters($request, ['search', 'status']);

        return DataTables::eloquent($this->repo->queryPages($filters))
            ->addIndexColumn()
            ->editColumn('page', fn (Page $page) => e($page->title ?? $page->slug))
            ->editColumn('slug', fn (Page $page) => e($page->slug))
            ->editColumn('status', fn (Page $page) => view('tenant.pages.store.pages._cols.status', ['page' => $page])->render())
            ->editColumn('updated_at', fn (Page $page) => $page->updated_at?->format('M d, Y'))
            ->addColumn('actions', fn (Page $page) => view('tenant.pages.store.pages._cols.actions', ['page' => $page])->render())
            ->rawColumns(['status', 'actions'])
            ->toJson();
    }

    public function destroy(Page $page): JsonResponse
    {
        $this->service->deleteModel($page);

        return $this->success('Page deleted successfully.');
    }
}
