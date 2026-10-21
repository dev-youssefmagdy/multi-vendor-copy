<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Finance;

use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Http\Requests\Tenant\Panel\Finance\PartnerWithdrawRequest;
use App\Models\Affiliate;
use App\Services\AffiliateService;
use App\Services\TenantAffiliateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

/**
 * Partner (affiliate) tab of the vendor wallet. Always scoped to the current
 * tenant's own affiliate row - no affiliate id is ever read from the request.
 */
final class PartnerWalletController extends PanelController
{
    public function __construct(
        private readonly TenantAffiliateService $partners,
        private readonly AffiliateService $affiliates,
    ) {
    }

    private function affiliate(): Affiliate
    {
        return $this->partners->forTenant((string) tenant('id'));
    }

    /** Opens the owner's affiliate account on the central domain (the form targets a new tab). */
    public function sso(): \Illuminate\Http\RedirectResponse
    {
        return redirect()->away($this->partners->loginUrl($this->affiliate()));
    }

    public function conversionsData(Request $request): JsonResponse
    {
        $query = $this->affiliate()->conversions()->with('package')->latest('id');

        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->addColumn('merchant', fn ($c) => e($this->mask((string) $c->tenant_id)))
            ->addColumn('package', fn ($c) => e((string) ($c->package?->name ?? '—')))
            ->addColumn('sale', fn ($c) => '$' . number_format((float) $c->sale_amount, 2))
            ->addColumn('commission', fn ($c) => '$' . number_format((float) $c->commission_amount, 2))
            ->addColumn('status', fn ($c) => $this->badge((string) $c->status))
            ->addColumn('date', fn ($c) => $c->created_at?->format('M d, Y'))
            ->removeColumn('tenant_id', 'payment_log_id', 'affiliate_id', 'affiliate_referral_id', 'affiliate_coupon_id', 'package_id', 'commission_type', 'commission_value', 'source', 'paid_amount')
            ->rawColumns(['status'])
            ->toJson();
    }

    public function payoutsData(Request $request): JsonResponse
    {
        $affiliate = $this->affiliate();

        $rows = $affiliate->payouts()->get()->map(fn ($p) => [
            'type'       => 'Payout',
            'amount'     => (float) $p->amount,
            'status'     => 'paid',
            'reference'  => (string) ($p->reference ?: '—'),
            'attachment' => $p->attachment_path,
            'at'         => $p->paid_at ?? $p->created_at,
        ])->concat(
            $affiliate->payoutRequests()->get()->map(fn ($r) => [
                'type'       => 'Request',
                'amount'     => (float) $r->amount,
                'status'     => (string) $r->status,
                'reference'  => '—',
                'attachment' => null,
                'at'         => $r->created_at,
            ])
        )->sortByDesc(fn ($r) => $r['at']?->getTimestamp() ?? 0)->values();

        return DataTables::collection($rows)
            ->addIndexColumn()
            ->editColumn('type', fn ($r) => e($r['type']))
            ->editColumn('amount', fn ($r) => '$' . number_format($r['amount'], 2))
            ->editColumn('status', fn ($r) => $this->badge($r['status']))
            ->editColumn('reference', fn ($r) => e($r['reference']))
            ->addColumn('attachment', fn ($r) => $r['attachment']
                ? '<a href="' . e($r['attachment']) . '" target="_blank" rel="noopener">View</a>'
                : '—')
            ->addColumn('date', fn ($r) => $r['at']?->format('M d, Y H:i'))
            ->rawColumns(['status', 'attachment'])
            ->toJson();
    }

    public function withdraw(PartnerWithdrawRequest $request): JsonResponse
    {
        $data = $request->validated();

        try {
            $this->affiliates->requestPayout($this->affiliate(), (float) $data['amount'], $data['note'] ?? null);
        } catch (\InvalidArgumentException $e) {
            return $this->failure($e->getMessage(), 422, ['amount' => [$e->getMessage()]]);
        }

        return $this->success(
            'Withdrawal request submitted. It will be reviewed shortly.',
            redirect: route('tenant.finance.wallet', ['tab' => 'partner']),
        );
    }

    private function mask(string $id): string
    {
        return Str::substr($id, 0, 2) . str_repeat('*', max(2, min(6, Str::length($id) - 2)));
    }

    private function badge(string $status): string
    {
        $cls = match ($status) {
            'paid', 'approved', 'completed' => 'green',
            'pending' => 'amber',
            'rejected' => 'red',
            'refunded', 'reversed' => 'violet',
            default => 'gray',
        };

        return '<span class="badge badge-' . $cls . '"><span class="badge-dot"></span>' . e(Str::headline($status)) . '</span>';
    }
}
