<?php

namespace App\Livewire\Admin\Order;

use App\Livewire\Admin\Base\ContentPage;
use App\Repositories\OrderRepository;

class OrdersReportPage extends ContentPage
{
    protected function pageMeta(): array
    {
        return [
            'title' => 'Orders Report',
            'badge' => 'Marketplace',
            'description' => 'Cross-tenant sales reporting, gateway mix, and owner-profit visibility built directly from tenant orders.',
            'contentIntro' => 'This report summarizes tenant-store orders into a central marketplace view for sales, payment, and profitability tracking.',
            'bullets' => [
                'Compare gross sales and owner profit over time.',
                'See how order lifecycle and gateway distribution are shifting across tenants.',
                'Use the top-tenant tables to spot the stores driving marketplace volume.',
            ],
        ];
    }

    protected function pageData(): array
    {
        $overview = app(OrderRepository::class)->reportOverview();

        return array_merge(parent::pageData(), [
            'cards' => $this->presentMetricCards($overview['cards']),
            'cardsGridClass' => 'g-stats4',
            'chartPayload' => $overview['chartPayload'],
            'chartSections' => $overview['chartSections'],
            'tableSections' => $overview['tableSections'],
        ]);
    }
}
