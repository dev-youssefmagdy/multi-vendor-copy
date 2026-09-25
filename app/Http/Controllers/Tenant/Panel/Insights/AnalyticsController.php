<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Insights;

use App\Http\Controllers\Tenant\Panel\PanelController;
use Illuminate\Contracts\View\View;

/**
 * Single Analytics page. Each former analytics page is now a tab rendered
 * inside this page; only the active tab's module is queried.
 */
final class AnalyticsController extends PanelController
{
    /** slug => [module controller, tab label, donut centre caption] */
    public const TABS = [
        'orders' => [OrderAnalyticsController::class, 'Orders analytics', 'ORDERS'],
        'customer-lifetime-value' => [CustomerLifetimeValueController::class, 'Customer Lifetime Value', 'CUSTOMERS'],
        'shipping' => [ShippingAnalyticsController::class, 'Shipping Analytics', 'ORDERS'],
        'profitability' => [ProductProfitabilityController::class, 'Product Profitability', 'ITEMS'],
    ];

    public function __invoke(?string $tab = null): View
    {
        $tab = array_key_exists((string) $tab, self::TABS) ? $tab : 'orders';
        [$module, , $donutCaption] = self::TABS[$tab];

        return view('tenant.pages.insights.index', app($module)->viewData() + [
            'activeTab' => $tab,
            'tabs' => collect(self::TABS)->map(fn (array $meta, string $slug) => [
                'label' => $meta[1],
                'url' => route('tenant.analytics', ['tab' => $slug]),
                'active' => $slug === $tab,
            ])->all(),
            'donutCaption' => $donutCaption,
        ]);
    }
}
