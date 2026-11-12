<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel;

use App\Http\Requests\Tenant\Panel\UiKitRequest;
use App\Support\Tenant\Metric;
use App\Support\Tenant\TableColumn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

final class UiKitController extends PanelController
{
    public function __invoke(): \Illuminate\View\View
    {
        $stats = Metric::cards([
            ['label' => __('Collected Sales'), 'value' => 3000, 'format' => 'currency', 'caption' => __('Paid order value captured in this tenant'), 'dot' => 'dot-cyan', 'trend' => 12],
            ['label' => __('Orders'), 'value' => 342, 'format' => 'number', 'caption' => __('Last 30 days'), 'dot' => 'dot-violet', 'trend' => -12],
            ['label' => __('Conversion'), 'value' => 3.42, 'format' => 'percent', 'caption' => __('Visitors to orders'), 'dot' => 'dot-green'],
        ]);

        $columns = [
            TableColumn::index(),
            TableColumn::make('name', __('Name')),
            TableColumn::make('status', __('Status')),
            TableColumn::actions(),
        ];

        return view('tenant.pages.ui-kit', [
            'stats' => $stats,
            'columns' => $columns,
            'languages' => [
                ['code' => 'en', 'name' => __('English'), 'is_default' => true, 'direction' => 'ltr'],
                ['code' => 'ar', 'name' => __('Arabic'), 'is_default' => false, 'direction' => 'rtl'],
            ],
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $rows = collect(range(1, 60))->map(fn (int $i) => [
            'id' => $i,
            'name' => "Sample row {$i}",
            'status' => $i % 3 === 0 ? 'pending' : ($i % 2 === 0 ? 'active' : 'rejected'),
        ]);

        return DataTables::collection($rows)
            ->addIndexColumn()
            ->addColumn('actions', fn () => '<button type="button" class="btn btn-secondary btn-sm">' . e(__('View')) . '</button>')
            ->rawColumns(['actions'])
            ->toJson();
    }

    public function validateForm(UiKitRequest $request): JsonResponse
    {
        return $this->validFormResponse();
    }

    public function store(UiKitRequest $request): JsonResponse
    {
        return $this->success(__('Demo form submitted successfully.'));
    }
}
