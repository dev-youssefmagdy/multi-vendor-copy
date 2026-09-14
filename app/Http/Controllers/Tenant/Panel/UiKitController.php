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
            ['label' => 'Revenue', 'value' => 128430.5, 'format' => 'currency', 'caption' => 'Last 30 days', 'dot' => 'dot-cyan'],
            ['label' => 'Orders', 'value' => 342, 'format' => 'number', 'caption' => 'Last 30 days', 'dot' => 'dot-violet'],
            ['label' => 'Conversion', 'value' => 3.42, 'format' => 'percent', 'caption' => 'Visitors to orders', 'dot' => 'dot-green'],
        ]);

        $columns = [
            TableColumn::index(),
            TableColumn::make('name', 'Name'),
            TableColumn::make('status', 'Status'),
            TableColumn::actions(),
        ];

        return view('tenant.pages.ui-kit', [
            'stats' => $stats,
            'columns' => $columns,
            'languages' => [
                ['code' => 'en', 'name' => 'English', 'is_default' => true, 'direction' => 'ltr'],
                ['code' => 'ar', 'name' => 'Arabic', 'is_default' => false, 'direction' => 'rtl'],
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
            ->addColumn('actions', fn () => '<button type="button" class="btn btn-secondary btn-sm">View</button>')
            ->rawColumns(['actions'])
            ->toJson();
    }

    public function validateForm(UiKitRequest $request): JsonResponse
    {
        return $this->validFormResponse();
    }

    public function store(UiKitRequest $request): JsonResponse
    {
        return $this->success('Demo form submitted successfully.');
    }
}
