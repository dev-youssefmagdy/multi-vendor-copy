<?php

namespace App\Livewire\Admin\Affiliate;

use App\Livewire\Admin\Base\ListPage;
use App\Livewire\Admin\Concerns\InteractsWithAdminUi;
use App\Models\AffiliateConversion;
use Livewire\WithPagination;

class ConversionsListPage extends ListPage
{
    use InteractsWithAdminUi, WithPagination;

    public string $statusFilter = '';

    protected function pageMeta(): array
    {
        return [
            'title'       => 'Affiliate Conversions',
            'badge'       => 'Affiliates',
            'description' => 'All referral conversions and commissions earned.',
            'actionLabel' => null,
            'tableTitle'  => 'Conversions',
            'headers'     => ['Affiliate', 'Tenant', 'Package', 'Sale', 'Commission', 'Type', 'Status', 'Date'],
        ];
    }

    protected function pageData(): array
    {
        $this->authorizePermission('affiliates.manage');

        $records = AffiliateConversion::query()
            ->with(['affiliate', 'package'])
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->latest()
            ->paginate(25);

        $totalCommissions = AffiliateConversion::query()->where('status', '!=', 'pending')->sum('commission_amount');
        $pendingCount     = AffiliateConversion::query()->where('status', 'pending')->count();
        $approvedCount    = AffiliateConversion::query()->where('status', 'approved')->count();

        $rows = collect($records->items())->map(fn (AffiliateConversion $c) => [
            e($c->affiliate?->name ?? '—'),
            e($c->tenant_id),
            e($c->package?->name ?? '—'),
            '$' . number_format((float) $c->sale_amount, 2),
            '<strong>$' . number_format((float) $c->commission_amount, 2) . '</strong>',
            '<span class="badge badge-cyan">' . e($c->commission_type === 'percentage' ? $c->commission_value . '%' : 'Fixed') . '</span>',
            '<span class="badge ' . match ($c->status) { 'approved' => 'badge-green', 'paid' => 'badge-secondary', default => 'badge-amber' } . '">' . ucfirst($c->status) . '</span>',
            e(optional($c->approved_at ?? $c->created_at)->format('M d, Y')),
        ])->all();

        return array_merge(parent::pageData(), [
            'records'    => $records,
            'rows'       => $rows,
            'statistics' => [
                ['label' => 'Total Commissions', 'value' => '$' . number_format((float) $totalCommissions, 2), 'dot' => 'dot-green', 'caption' => 'Earned (approved + paid)'],
                ['label' => 'Pending', 'value' => number_format($pendingCount), 'dot' => 'dot-amber', 'caption' => 'Awaiting paid package'],
                ['label' => 'Approved', 'value' => number_format($approvedCount), 'dot' => 'dot-cyan', 'caption' => 'Added to affiliate balance'],
            ],
            'filterFields' => [
                ['label' => 'Status', 'model' => 'statusFilter', 'type' => 'select', 'options' => ['' => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'paid' => 'Paid']],
            ],
        ]);
    }

    public function clearFilters(): void
    {
        $this->statusFilter = '';
    }
}
