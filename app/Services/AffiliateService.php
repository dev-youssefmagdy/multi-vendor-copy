<?php

namespace App\Services;

use App\Models\Affiliate;
use App\Models\AffiliateConversion;
use App\Models\AffiliateCoupon;
use App\Models\AffiliatePayout;
use App\Models\AffiliatePayoutRequest;
use App\Models\AffiliateReferral;
use App\Models\PaymentLog;
use App\Models\Tenant;
use App\Support\AffiliateSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AffiliateService
{
    public const COOKIE_NAME = 'neo_aff';

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
            AffiliateSettings::cookieDays() * 24 * 60,
            '/', null, $request->isSecure(), true, false, 'Lax'
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
            ->whereNull('converted_at')
            ->where('id', (int) $referralId)
            ->whereHas('affiliate', fn($q) => $q->where('status', 'active'))
            ->first();
    }

    // ── Conversion handling ────────────────────────────────────────

    /**
     * Mark a referral as "signed up" (tenant created, possibly on free plan).
     * The conversion stays "pending" until a paid package is bought.
     * Safe against double calls: only the first caller converts the referral.
     */
    public function markReferralConverted(AffiliateReferral $referral, string $tenantId): void
    {
        DB::connection(config('tenancy.database.central_connection'))->transaction(function () use ($referral, $tenantId) {
            $locked = AffiliateReferral::query()->lockForUpdate()->find($referral->id);

            if (!$locked || $locked->converted_at) {
                return;
            }

            $locked->update([
                'tenant_id'    => $tenantId,
                'converted_at' => now(),
            ]);

            $affiliate = $locked->affiliate;

            AffiliateConversion::query()->updateOrCreate(
                ['affiliate_id' => $locked->affiliate_id, 'tenant_id' => $tenantId],
                [
                    'affiliate_referral_id' => $locked->id,
                    'package_id'            => null,
                    'payment_log_id'        => null,
                    'sale_amount'           => 0.00,
                    'commission_amount'     => 0.00,
                    'commission_type'       => $affiliate->commission_type,
                    'commission_value'      => $affiliate->commission_value,
                    'status'                => 'pending',
                ]
            );
        });
    }

    /**
     * Called when a tenant completes a PAID package payment.
     * Approves the tenant's pending referral conversion (first paid payment only;
     * renewals never earn commission). Idempotent per payment log.
     */
    public function approveConversion(string $tenantId, PaymentLog $paymentLog, ?int $couponAffiliateId = null): ?AffiliateConversion
    {
        // An affiliated coupon takes priority — avoids double-attribution.
        if ($couponAffiliateId !== null || (float) $paymentLog->amount <= 0) {
            return null;
        }

        $conversion = DB::connection(config('tenancy.database.central_connection'))->transaction(function () use ($tenantId, $paymentLog) {
            if (AffiliateConversion::query()->where('payment_log_id', $paymentLog->id)->exists()) {
                return null;
            }

            $conversion = AffiliateConversion::query()
                ->where('tenant_id', $tenantId)
                ->where('status', 'pending')
                ->lockForUpdate()
                ->first();

            if (!$conversion) {
                return null;
            }

            $affiliate = Affiliate::query()->lockForUpdate()->find($conversion->affiliate_id);

            // Suspended / unapproved affiliates earn nothing; the conversion stays pending.
            if (!$affiliate || !$affiliate->isActive()) {
                return null;
            }

            if ($this->isSelfReferral($affiliate, $tenantId)) {
                $conversion->update(['status' => 'rejected']);
                return null;
            }

            // Use the rate snapshotted at signup time, not the affiliate's current one.
            $commission = Affiliate::commissionFor(
                $conversion->commission_type,
                (float) $conversion->commission_value,
                (float) $paymentLog->amount
            );

            $conversion->update([
                'payment_log_id'    => $paymentLog->id,
                'package_id'        => $paymentLog->package_id,
                'sale_amount'       => $paymentLog->amount,
                'commission_amount' => $commission,
                'status'            => 'approved',
                'approved_at'       => now(),
            ]);

            $this->credit($affiliate, $commission);

            return $conversion;
        });

        if ($conversion) {
            // Prevent double-attribution on future purchases from this browser.
            cookie()->queue(cookie()->forget(self::COOKIE_NAME));
        }

        return $conversion;
    }

    /**
     * Called when a tenant pays for a package using an affiliate-linked coupon code.
     * Independent of the URL-referral cookie. If a pending/approved conversion already
     * exists for the same affiliate+tenant it is upgraded (higher commission wins,
     * only the difference is credited). Idempotent per payment log.
     */
    public function approveCouponConversion(string $tenantId, PaymentLog $paymentLog, AffiliateCoupon $coupon): ?AffiliateConversion
    {
        return DB::connection(config('tenancy.database.central_connection'))->transaction(function () use ($tenantId, $paymentLog, $coupon) {
            if (AffiliateConversion::query()->where('payment_log_id', $paymentLog->id)->exists()) {
                return null;
            }

            $affiliate = Affiliate::query()->lockForUpdate()->find($coupon->affiliate_id);

            if (!$affiliate || !$affiliate->isActive() || $this->isSelfReferral($affiliate, $tenantId)) {
                return null;
            }

            $commissionAmount = $coupon->calculateCommission((float) $paymentLog->amount);

            if ($commissionAmount <= 0) {
                return null;
            }

            $commissionValue = $coupon->commission_value ?? $affiliate->commission_value;

            $existing = AffiliateConversion::query()
                ->where('affiliate_id', $affiliate->id)
                ->where('tenant_id', $tenantId)
                ->whereIn('status', ['pending', 'approved'])
                ->lockForUpdate()
                ->first();

            $attributes = [
                'affiliate_coupon_id' => $coupon->id,
                'source'              => 'coupon',
                'payment_log_id'      => $paymentLog->id,
                'package_id'          => $paymentLog->package_id,
                'sale_amount'         => $paymentLog->amount,
                'status'              => 'approved',
                'approved_at'         => now(),
            ];

            if ($existing) {
                $previous    = $existing->status === 'approved' ? (float) $existing->commission_amount : 0.0;
                $final       = max($commissionAmount, $previous);
                $isPercent   = $coupon->commission_value !== null || $affiliate->commission_type === 'percentage';

                $existing->update($attributes + [
                    'commission_amount' => $final,
                    'commission_type'   => $isPercent ? 'percentage' : 'fixed',
                    'commission_value'  => $commissionValue,
                ]);

                $this->credit($affiliate, max(0.0, $final - $previous));

                return $existing->fresh();
            }

            $conversion = AffiliateConversion::query()->create($attributes + [
                'affiliate_id'          => $affiliate->id,
                'affiliate_referral_id' => null,
                'tenant_id'             => $tenantId,
                'commission_amount'     => $commissionAmount,
                'commission_type'       => ($coupon->commission_value !== null || $affiliate->commission_type === 'percentage') ? 'percentage' : 'fixed',
                'commission_value'      => $commissionValue,
            ]);

            $this->credit($affiliate, $commissionAmount);

            return $conversion;
        });
    }

    /**
     * Reverse an approved/paid commission (refund or admin action).
     * Balance may go negative when part of the commission was already paid out;
     * that debt is netted against future earnings and blocks new payouts.
     */
    public function reverseConversion(AffiliateConversion $conversion): bool
    {
        return DB::connection(config('tenancy.database.central_connection'))->transaction(function () use ($conversion) {
            $locked = AffiliateConversion::query()->lockForUpdate()->find($conversion->id);

            if (!$locked || !in_array($locked->status, ['approved', 'paid'], true)) {
                return false;
            }

            $affiliate  = Affiliate::query()->lockForUpdate()->find($locked->affiliate_id);
            $commission = (float) $locked->commission_amount;

            $locked->update(['status' => 'refunded', 'refunded_at' => now()]);

            $affiliate?->decrement('balance', $commission);
            $affiliate?->decrement('total_earned', $commission);

            return true;
        });
    }

    /** Reverse the commission tied to a refunded payment, if any. */
    public function reverseForPaymentLog(PaymentLog $paymentLog): bool
    {
        $conversion = AffiliateConversion::query()->where('payment_log_id', $paymentLog->id)->first();

        return $conversion ? $this->reverseConversion($conversion) : false;
    }

    // ── Coupons ────────────────────────────────────────────────────

    /**
     * Validate an affiliate coupon for a package price.
     *
     * @return array{0: ?AffiliateCoupon, 1: ?string} [coupon, errorMessage]
     */
    public function validateCoupon(string $code, float $price, ?string $email = null): array
    {
        $coupon = AffiliateCoupon::query()
            ->with('affiliate')
            ->where('code', strtoupper(trim($code)))
            ->active()
            ->first();

        if (!$coupon || !$coupon->affiliate?->isActive()) {
            return [null, __('Invalid or expired coupon code.')];
        }

        if ($price < (float) $coupon->minimum_spend) {
            return [null, __('This coupon requires a minimum spend of $:amount.', ['amount' => number_format((float) $coupon->minimum_spend, 2)])];
        }

        if ($email && strcasecmp(trim($email), $coupon->affiliate->email) === 0) {
            return [null, __('You cannot use your own affiliate coupon.')];
        }

        return [$coupon, null];
    }

    // ── Payouts ────────────────────────────────────────────────────

    /**
     * Admin issues a payout. The amount is allocated to approved conversions oldest-first,
     * partially if needed, so `paid_amount` always reconciles with payouts issued.
     */
    public function issuePayout(Affiliate $affiliate, array $payoutData): AffiliatePayout
    {
        return DB::connection(config('tenancy.database.central_connection'))->transaction(function () use ($affiliate, $payoutData) {
            $amount    = round((float) $payoutData['amount'], 2);
            $affiliate = Affiliate::query()->lockForUpdate()->findOrFail($affiliate->id);

            if ($amount <= 0 || $amount > (float) $affiliate->balance) {
                throw new \InvalidArgumentException('Invalid payout amount.');
            }

            $min = AffiliateSettings::minPayout();
            if ($amount < $min && $amount < (float) $affiliate->balance) {
                throw new \InvalidArgumentException('Payout is below the minimum of $' . number_format($min, 2) . '.');
            }

            $affiliate->decrement('balance', $amount);
            $affiliate->increment('total_paid', $amount);

            $remaining = $amount;
            AffiliateConversion::query()
                ->where('affiliate_id', $affiliate->id)
                ->where('status', 'approved')
                ->orderBy('approved_at')
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->each(function (AffiliateConversion $c) use (&$remaining) {
                    if ($remaining <= 0) {
                        return false;
                    }

                    $due  = round((float) $c->commission_amount - (float) $c->paid_amount, 2);
                    $take = min($due, $remaining);
                    $remaining = round($remaining - $take, 2);

                    $paid = round((float) $c->paid_amount + $take, 2);
                    $c->update($paid >= (float) $c->commission_amount
                        ? ['paid_amount' => $paid, 'status' => 'paid', 'paid_at' => now()]
                        : ['paid_amount' => $paid]);
                });

            $payout = AffiliatePayout::query()->create([
                'affiliate_id'    => $affiliate->id,
                'amount'          => $amount,
                'method'          => $payoutData['method'] ?? 'manual',
                'reference'       => $payoutData['reference'] ?? null,
                'notes'           => $payoutData['notes'] ?? null,
                'attachment_path' => $payoutData['attachment_path'] ?? null,
                'paid_at'         => now(),
            ]);

            // Close the matching pending request(s) so they are not paid twice.
            AffiliatePayoutRequest::query()
                ->where('affiliate_id', $affiliate->id)
                ->where('status', 'pending')
                ->when(
                    !empty($payoutData['request_id']),
                    fn ($q) => $q->where('id', $payoutData['request_id']),
                    fn ($q) => $q->where('amount', '<=', $amount)
                )
                ->update(['status' => 'fulfilled', 'affiliate_payout_id' => $payout->id, 'processed_at' => now()]);

            return $payout;
        });
    }

    /** Amount the affiliate may still request (balance minus requests already pending). */
    public function requestableAmount(Affiliate $affiliate): float
    {
        $pending = (float) $affiliate->payoutRequests()->where('status', 'pending')->sum('amount');

        return max(0.0, round((float) $affiliate->balance - $pending, 2));
    }

    public function requestPayout(Affiliate $affiliate, float $amount, ?string $note = null): AffiliatePayoutRequest
    {
        return DB::connection(config('tenancy.database.central_connection'))->transaction(function () use ($affiliate, $amount, $note) {
            $affiliate = Affiliate::query()->lockForUpdate()->findOrFail($affiliate->id);
            $amount    = round($amount, 2);
            $min       = AffiliateSettings::minPayout();

            if (!$affiliate->isActive()) {
                throw new \InvalidArgumentException(__('Your account is not active.'));
            }
            if ($amount <= 0 || $amount < $min) {
                throw new \InvalidArgumentException(__('The minimum payout is $:amount.', ['amount' => number_format($min, 2)]));
            }
            if ($amount > $this->requestableAmount($affiliate)) {
                throw new \InvalidArgumentException(__('Amount exceeds your available balance.'));
            }

            return $affiliate->payoutRequests()->create([
                'amount' => $amount,
                'note'   => $note,
                'status' => 'pending',
            ]);
        });
    }

    public function rejectPayoutRequest(AffiliatePayoutRequest $request, ?string $adminNote = null): void
    {
        if ($request->status === 'pending') {
            $request->update(['status' => 'rejected', 'admin_note' => $adminNote, 'processed_at' => now()]);
        }
    }

    // ── Internals ──────────────────────────────────────────────────

    private function credit(Affiliate $affiliate, float $amount): void
    {
        if ($amount > 0) {
            $affiliate->increment('balance', $amount);
            $affiliate->increment('total_earned', $amount);
        }
    }

    /** An affiliate must not earn commission on their own signup. */
    private function isSelfReferral(Affiliate $affiliate, string $tenantId): bool
    {
        // `email` is a virtual (JSON data) attribute on Tenant, so load the model.
        if ($affiliate->tenant_id !== null && (string) $affiliate->tenant_id === $tenantId) {
            return true;
        }

        $email = Tenant::query()->find($tenantId)?->email;
        if (! $email) {
            return false;
        }

        $email = trim((string) $email);
        if (strcasecmp($email, $affiliate->email) === 0) {
            return true;
        }

        if ($affiliate->tenant_id) {
            $owner = Tenant::query()->find($affiliate->tenant_id)?->email;
            return $owner && strcasecmp(trim((string) $owner), $email) === 0;
        }

        return false;
    }
}
