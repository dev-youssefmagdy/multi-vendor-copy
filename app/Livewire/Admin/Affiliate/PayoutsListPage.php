<?php

namespace App\Livewire\Admin\Affiliate;

use App\Livewire\Admin\Base\ListPage;
use App\Livewire\Admin\Concerns\InteractsWithAdminUi;
use App\Models\AffiliatePayout;
use Livewire\WithPagination;

class PayoutsListPage extends ListPage
{
    use InteractsWithAdminUi, WithPagination;

    protected function pageMeta(): array
    {
        return [
            'title'       => 'Affiliate Payouts',
            'badge'       => 'Affiliates',
            'description' => 'All affiliate payout records.',
            'actionLabel' => null,
            'tableTitle'  => 'Payouts',
            'headers'     => ['Affiliate', 'Amount', 'Method', 'Reference', 'Date'],
        ];
    }

    protected function pageData(): array
    {
        $this->authorizePermission('affiliates.manage');

        $records = AffiliatePayout::query()
            ->with('affiliate')
            ->latest('paid_at')
            ->paginate(25);

        $totalPaid = AffiliatePayout::query()->sum('amount');

        $rows = collect($records->items())->map(fn (AffiliatePayout $p) => [
            e($p->affiliate?->name ?? '—'),
            '<strong>$' . number_format((float) $p->amount, 2) . '</strong>',
            e(ucfirst(str_replace('_', ' ', $p->method))),
            e($p->reference ?? '—'),
            e($p->paid_at->format('M d, Y')),
        ])->all();

        return array_merge(parent::pageData(), [
            'records'    => $records,
            'rows'       => $rows,
            'statistics' => [
                ['label' => 'Total Paid Out', 'value' => '$' . number_format((float) $totalPaid, 2), 'dot' => 'dot-cyan', 'caption' => 'All-time payouts issued'],
            ],
        ]);
    }

    public function clearFilters(): void {}
}
