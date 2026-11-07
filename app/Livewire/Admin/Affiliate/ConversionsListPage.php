<?php

namespace App\Livewire\Admin\Affiliate;

use App\Livewire\Admin\Base\ListPage;
use App\Livewire\Admin\Concerns\InteractsWithAdminUi;
use App\Livewire\Admin\Concerns\ManagesAffiliateSettings;
use App\Models\AffiliateConversion;
use App\Services\AffiliateService;
use Livewire\WithPagination;

class ConversionsListPage extends ListPage
{
    use InteractsWithAdminUi, ManagesAffiliateSettings, WithPagination;

    public string $statusFilter = '';

    protected function pageView(): string
    {
        return 'livewire.admin.affiliate.conversions-list-page';
    }

    public function closeModal(): void
    {
        $this->showSettingsModal = false;
        $this->resetErrorBag();
    }

    protected function pageMeta(): array
    {
        return [
            'title'       => 'Affiliate Conversions',
            'badge'       => 'Affiliates',
            'description' => 'All referral conversions and commissions earned.',
            'actionLabel' => null,
            'tableTitle'  => 'Conversions',
            'headers'     => ['Affiliate', 'Tenant', 'Package', 'Sale', 'Commission', 'Type', 'Status', 'Date', 'Actions'],
        ];
    }

    protected function pageData(): array
    {
        $this->authorizePermission('affiliates.manage');
        $service = app(AffiliateService::class);

        $records = AffiliateConversion::query()
            ->with(['affiliate', 'package'])
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->latest()
            ->paginate(25);

        $totalCommissions = AffiliateConversion::query()->whereIn('status', ['approved', 'paid'])->sum('commission_amount');
        $pendingCount     = AffiliateConversion::query()->where('status', 'pending')->count();
        $approvedCount    = AffiliateConversion::query()->where('status', 'approved')->count();

        $rows = collect($records->items())->map(fn (AffiliateConversion $c) => [
            e($c->affiliate?->name ?? '—'),
            e($c->tenant_id),
            e($c->package?->name ?? '—'),
            '$' . number_format((float) $c->sale_amount, 2),
            '<strong>$' . number_format((float) $c->commission_amount, 2) . '</strong>',
            '<span class="badge badge-cyan">' . e($c->commission_type === 'percentage' ? $c->commission_value . '%' : 'Fixed') . '</span>',
            '<span class="badge ' . match ($c->status) { 'approved' => 'badge-green', 'paid' => 'badge-secondary', 'refunded', 'rejected' => 'badge-red', default => 'badge-amber' } . '">' . ucfirst($c->status) . '</span>'
                . $this->statusHint($c, $service),
            e(optional($c->approved_at ?? $c->created_at)->format('M d, Y')),
            in_array($c->status, ['approved', 'paid'], true)
                ? '<button type="button" class="btn btn-secondary btn-sm" wire:click="reverse(' . $c->id . ')" wire:confirm="Reverse this commission? The affiliate balance will be reduced.">Reverse</button>'
                : '',
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
                ['label' => 'Status', 'model' => 'statusFilter', 'type' => 'select', 'options' => ['' => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'paid' => 'Paid', 'refunded' => 'Refunded', 'rejected' => 'Rejected']],
            ],
        ]);
    }

    /** Small explanatory line under the status badge (derived — no schema change). */
    private function statusHint(AffiliateConversion $c, AffiliateService $service): string
    {
        $hint = match ($c->status) {
            'pending'  => 'Signed up - awaiting first paid package',
            'rejected' => $service->rejectionReason($c),
            default    => null,
        };

        return $hint ? '<div class="field-hint" style="margin-top:4px;">' . e($hint) . '</div>' : '';
    }

    public function reverse(int $id, AffiliateService $service): void
    {
        $this->authorizePermission('affiliates.manage');

        $ok = $service->reverseConversion(AffiliateConversion::query()->findOrFail($id));
        $this->toast($ok ? 'Commission reversed.' : 'Conversion cannot be reversed.');
    }

    public function clearFilters(): void
    {
        $this->statusFilter = '';
    }
}
