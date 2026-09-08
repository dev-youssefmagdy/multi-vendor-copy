<?php

namespace App\Livewire\Admin\Affiliate;

use App\Livewire\Admin\Base\ContentPage;
use App\Livewire\Admin\Concerns\InteractsWithAdminUi;
use App\Models\Affiliate;
use App\Models\AffiliateConversion;
use App\Models\AffiliatePayout;
use Illuminate\Support\Facades\DB;

class AffiliateReportsPage extends ContentPage
{
    use InteractsWithAdminUi;

    protected function pageMeta(): array
    {
        return [
            'title'       => 'Affiliate Reports',
            'badge'       => 'Affiliates',
            'description' => 'Performance overview, commission breakdown, and payout summaries.',
        ];
    }

    protected function pageData(): array
    {
        $this->authorizePermission('affiliates.manage');

        $topAffiliates = Affiliate::query()
            ->where('total_earned', '>', 0)
            ->orderByDesc('total_earned')
            ->take(10)
            ->get(['id', 'name', 'email', 'commission_type', 'commission_value', 'total_earned', 'total_paid', 'balance']);

        $monthlyCommissions = AffiliateConversion::query()
            ->where('status', '!=', 'pending')
            ->where('approved_at', '>=', now()->subMonths(12))
            ->select(
                DB::raw("DATE_FORMAT(approved_at, '%Y-%m') as month"),
                DB::raw('SUM(commission_amount) as total'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $summary = [
            'total_affiliates'  => Affiliate::query()->count(),
            'active_affiliates' => Affiliate::query()->where('status', 'active')->count(),
            'total_conversions' => AffiliateConversion::query()->where('status', '!=', 'pending')->count(),
            'total_commissions' => AffiliateConversion::query()->where('status', '!=', 'pending')->sum('commission_amount'),
            'total_paid_out'    => AffiliatePayout::query()->sum('amount'),
            'pending_balance'   => Affiliate::query()->sum('balance'),
        ];

        $cards = [
            ['label' => 'Total Affiliates', 'value' => number_format($summary['total_affiliates']), 'dot' => 'dot-cyan', 'caption' => $summary['active_affiliates'] . ' active'],
            ['label' => 'Total Conversions', 'value' => number_format($summary['total_conversions']), 'dot' => 'dot-green', 'caption' => 'Approved and paid'],
            ['label' => 'Total Commissions', 'value' => '$' . number_format((float) $summary['total_commissions'], 2), 'dot' => 'dot-amber', 'caption' => 'Earned by affiliates'],
            ['label' => 'Total Paid Out', 'value' => '$' . number_format((float) $summary['total_paid_out'], 2), 'dot' => 'dot-cyan', 'caption' => '$' . number_format((float) $summary['pending_balance'], 2) . ' pending balance'],
        ];

        $topAffiliatesRows = $topAffiliates->map(fn (Affiliate $a) => [
            '<div class="entity-title">' . e($a->name) . '</div><div class="entity-subtitle">' . e($a->email) . '</div>',
            '<span class="badge badge-cyan">' . e($a->commission_type === 'percentage' ? $a->commission_value . '%' : '$' . number_format((float) $a->commission_value, 2)) . '</span>',
            '$' . number_format((float) $a->total_earned, 2),
            '$' . number_format((float) $a->total_paid, 2),
            '<strong>$' . number_format((float) $a->balance, 2) . '</strong>',
        ])->all();

        $monthlyRows = $monthlyCommissions->map(fn ($m) => [
            e($m->month),
            number_format($m->count),
            '$' . number_format((float) $m->total, 2),
        ])->all();

        return array_merge(parent::pageData(), [
            'cards'         => $cards,
            'tableSections' => [
                [
                    'title'   => 'Top Affiliates',
                    'headers' => ['Affiliate', 'Commission', 'Total Earned', 'Total Paid', 'Balance'],
                    'rows'    => $topAffiliatesRows,
                ],
                [
                    'title'   => 'Monthly Commissions (Last 12 Months)',
                    'headers' => ['Month', 'Conversions', 'Commission Total'],
                    'rows'    => $monthlyRows,
                ],
            ],
        ]);
    }
}
