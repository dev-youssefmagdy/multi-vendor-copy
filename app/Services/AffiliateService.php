<?php

namespace App\Services;

use App\Models\Affiliate;
use App\Models\AffiliateConversion;
use App\Models\AffiliateReferral;
use App\Models\AffiliatePayout;
use App\Models\PaymentLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AffiliateService
{
    public const COOKIE_NAME  = 'neo_aff';
    public const COOKIE_DAYS  = 90; // attribution window

    // ── Referral tracking ──────────────────────────────────────────

    /**
     * Called when ?ref=CODE is detected in any website URL.
     * Creates a referral record and sets a tracking cookie.
     */
    public function trackClick(Affiliate $affiliate, Request $request): AffiliateReferral
    {
        $referral = AffiliateReferral::query()->create([
            'affiliate_id' => $affiliate->id,
            'ip'           => $request->ip(),
            'user_agent'   => $request->userAgent(),
            'landing_url'  => $request->fullUrl(),
        ]);

        cookie()->queue(
            self::COOKIE_NAME,
            $referral->id,
            self::COOKIE_DAYS * 24 * 60,
            '/', null, true, true, false, 'Lax'
        );

        return $referral;
    }

    /**
     * Resolve which referral (if any) should get credit for this registration.
     * Reads the tracking cookie; returns null if no valid referral found.
     */
    public function resolveReferral(Request $request): ?AffiliateReferral
    {
        $referralId = $request->cookie(self::COOKIE_NAME);
        if (!$referralId) {
            return null;
        }

        return AffiliateReferral::query()
            ->whereNull('converted_at')  // not yet converted
            ->where('id', (int) $referralId)
            ->whereHas('affiliate', fn($q) => $q->where('status', 'active'))
            ->first();
    }

    // ── Conversion handling ────────────────────────────────────────

    /**
     * Mark a referral as "signed up" (tenant created, possibly on free plan).
     * The conversion status will remain "pending" until a paid package is bought.
     */
    public function markReferralConverted(AffiliateReferral $referral, string $tenantId): void
    {
        $referral->update([
            'tenant_id'    => $tenantId,
            'converted_at' => now(),
        ]);

        // Create a pending conversion (no commission yet — waiting for paid package)
        AffiliateConversion::query()->updateOrCreate(
            ['affiliate_id' => $referral->affiliate_id, 'tenant_id' => $tenantId],
            [
                'affiliate_referral_id' => $referral->id,
                'package_id'            => null,
                'payment_log_id'        => null,
                'sale_amount'           => 0.00,
                'commission_amount'     => 0.00,
                'commission_type'       => $referral->affiliate->commission_type,
                'commission_value'      => $referral->affiliate->commission_value,
                'status'                => 'pending',
            ]
        );
    }

    /**
     * Called when a tenant completes a PAID package payment.
     * Finds any pending conversion for this tenant and approves it.
     */
    public function approveConversion(string $tenantId, PaymentLog $paymentLog): ?AffiliateConversion
    {
        $conversion = AffiliateConversion::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'pending')
            ->with('affiliate')
            ->first();

        if (!$conversion) {
            return null; // no pending conversion — tenant didn't come from affiliate
        }

        $affiliate       = $conversion->affiliate;
        $commissionAmount = $affiliate->calculateCommission((float) $paymentLog->amount);

        $conversion = DB::transaction(function () use ($conversion, $paymentLog, $commissionAmount) {
            $conversion->update([
                'payment_log_id'    => $paymentLog->id,
                'package_id'        => $paymentLog->package_id,
                'sale_amount'       => $paymentLog->amount,
                'commission_amount' => $commissionAmount,
                'status'            => 'approved',
                'approved_at'       => now(),
            ]);

            // Credit the affiliate's balance
            $conversion->affiliate->increment('balance', $commissionAmount);
            $conversion->affiliate->increment('total_earned', $commissionAmount);

            return $conversion;
        });

        // Prevent double-attribution on future purchases from this browser.
        cookie()->queue(cookie()->forget(self::COOKIE_NAME));

        return $conversion;
    }

    // ── Payouts ────────────────────────────────────────────────────

    /**
     * Admin issues a payout to an affiliate.
     * Marks all approved conversions as paid and records the payout.
     */
    public function issuePayout(Affiliate $affiliate, array $payoutData): AffiliatePayout
    {
        return DB::transaction(function () use ($affiliate, $payoutData) {
            $amount = (float) $payoutData['amount'];

            if ($amount <= 0 || $amount > (float) $affiliate->balance) {
                throw new \InvalidArgumentException('Invalid payout amount.');
            }

            // Deduct from balance
            $affiliate->decrement('balance', $amount);
            $affiliate->increment('total_paid', $amount);

            // Mark approved conversions as paid (oldest first, up to payout amount)
            $remaining = $amount;
            AffiliateConversion::query()
                ->where('affiliate_id', $affiliate->id)
                ->where('status', 'approved')
                ->orderBy('approved_at')
                ->get()
                ->each(function (AffiliateConversion $c) use (&$remaining) {
                    if ($remaining <= 0) return false;
                    if ((float) $c->commission_amount <= $remaining) {
                        $remaining -= (float) $c->commission_amount;
                        $c->update(['status' => 'paid', 'paid_at' => now()]);
                    }
                });

            return AffiliatePayout::query()->create([
                'affiliate_id' => $affiliate->id,
                'amount'       => $amount,
                'method'       => $payoutData['method'] ?? 'manual',
                'reference'    => $payoutData['reference'] ?? null,
                'notes'        => $payoutData['notes'] ?? null,
                'paid_at'      => now(),
            ]);
        });
    }
}
