<?php

declare(strict_types=1);

// ROUTES:
// Route::get('/subscribers/data', [SubscribersController::class, 'data'])->name('subscribers.data');
// Route::get('/subscribers/export', [SubscribersController::class, 'export'])->name('subscribers.export');
// Route::delete('/subscribers/{subscriber}', [SubscribersController::class, 'destroy'])->name('subscribers.destroy');
// (all under the existing tenant.permission:store.subscribers.manage middleware, next to the kept `subscribers` GET route)

namespace App\Http\Controllers\Tenant\Panel\Settings;

use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Models\Tenant\Subscriber;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Services\Tenant\TenantPanelService;
use App\Support\Tenant\Metric;
use App\Support\Tenant\TableColumn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Yajra\DataTables\Facades\DataTables;

final class SubscribersController extends PanelController
{
    public function __construct(
        private readonly TenantPanelRepository $repo,
        private readonly TenantPanelService $service,
    ) {
    }

    public function index(): View
    {
        $stats = $this->repo->subscriberStats();

        return view('tenant.pages.settings.subscribers.index', [
            'stats' => Metric::cards([
                ['label' => __('Subscribers'), 'value' => $stats['total'], 'format' => 'number', 'caption' => __('All storefront subscribers'), 'dot' => 'dot-cyan'],
                ['label' => __('This Month'), 'value' => $stats['new_this_month'], 'format' => 'number', 'caption' => __('New subscriber growth this month'), 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => __('This Week'), 'value' => $stats['new_this_week'], 'format' => 'number', 'caption' => __('Recent weekly signups'), 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
            ]),
            'columns' => [
                TableColumn::index(),
                TableColumn::make('email', __('Email')),
                TableColumn::make('created_at', __('Subscribed At')),
                TableColumn::actions(),
            ],
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $filters = $this->filters($request, ['search']);
        $query = $this->repo->querySubscribers($filters);

        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->editColumn('email', fn (Subscriber $subscriber) => e($subscriber->email))
            ->editColumn('created_at', fn (Subscriber $subscriber) => $subscriber->created_at?->format('M d, Y H:i'))
            ->addColumn('actions', fn (Subscriber $subscriber) => view('tenant.pages.settings.subscribers._cols.actions', ['subscriber' => $subscriber])->render())
            ->rawColumns(['actions'])
            ->toJson();
    }

    public function export(): StreamedResponse
    {
        $rows = $this->repo->exportSubscribers()
            ->map(fn (Subscriber $subscriber) => [
                $subscriber->email,
                $subscriber->created_at?->format('Y-m-d H:i') ?? '',
            ]);

        return $this->streamCsv(
            'subscribers-'.now()->format('Y-m-d').'.csv',
            [__('Email'), __('Subscribed At')],
            $rows,
        );
    }

    public function destroy(Subscriber $subscriber): JsonResponse
    {
        $this->service->deleteSubscriber($subscriber);

        return $this->success(__('Subscriber deleted successfully.'));
    }
}
