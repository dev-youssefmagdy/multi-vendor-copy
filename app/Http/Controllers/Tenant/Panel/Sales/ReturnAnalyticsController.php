<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Sales;

use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Support\Tenant\Metric;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Yajra\DataTables\Facades\DataTables;

final class ReturnAnalyticsController extends PanelController
{
    public function __construct(private readonly TenantPanelRepository $repo)
    {
    }

    private function overview(): array
    {
        return $this->repo->returnAnalyticsOverview();
    }

    public function index(): View
    {
        $overview = $this->overview();

        return view('tenant.pages.insights._layout', [
            'title' => 'Return Analytics',
            'badge' => 'Store',
            'description' => 'Return-request trends, reasons, and processing time for your store.',
            'contentIntro' => 'Approval rates, rejection rates, and processing time are aggregated directly from your store\'s return requests.',
            'cardsGridClass' => 'g-stats4',
            'cards' => Metric::cards($overview['cards']),
            'bullets' => [
                'Approval rate counts approved and refunded requests together.',
                'Processing time is measured from submission to the final status update.',
                'Top reasons and products reflect all-time return requests for this store.',
            ],
            'chartSections' => [
                [
                    'layoutClass' => 'g-r2',
                    'cards' => [
                        [
                            'title' => 'Top 5 Return Reasons',
                            'metrics' => $overview['top_reasons'],
                        ],
                        [
                            'title' => 'Top 5 Most Returned Products',
                            'metrics' => $overview['top_products'],
                        ],
                    ],
                ],
            ],
            'tables' => [
                [
                    'id' => 'return-analytics-monthly',
                    'url' => route('tenant.returns.analytics.data'),
                    'title' => 'Monthly Trend (Last 6 Months)',
                    'description' => 'Return requests submitted per month.',
                    'columns' => [
                        ['data' => 'label', 'title' => 'Month', 'orderable' => false],
                        ['data' => 'count', 'title' => 'Return Requests', 'orderable' => false],
                    ],
                    'order' => [],
                ],
            ],
        ]);
    }

    public function data(): JsonResponse
    {
        $rows = collect($this->overview()['monthly_rows']);

        return DataTables::collection($rows)
            ->editColumn('count', fn (array $row) => number_format($row['count']))
            ->toJson();
    }
}
