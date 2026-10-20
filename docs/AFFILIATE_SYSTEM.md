# Affiliate system

Central-panel only. Affiliates earn commission when a referred tenant buys a **paid subscription package**. Unrelated to vendor orders (`ORDER_FORMULAS.md` does not apply).

## Flow
1. `?ref=CODE` (middleware `track.affiliate`) → `affiliate_referrals` row + cookie `neo_aff` (window = `AffiliateSettings::cookieDays()`, first referrer wins).
2. Signup → `AffiliateService::markReferralConverted` (race-safe, once) → `pending` conversion with the affiliate's rate **snapshot**.
3. First paid payment → `approveConversion` (idempotent per `payment_log_id`, unique index). Renewals never earn commission.
4. Coupon (`affiliate_coupons`) → `approveCouponConversion`; replaces/upgrades the referral conversion (higher commission wins, only the difference is credited). Coupon + URL referral never double-pay.

## Rules
- Only `active` affiliates earn; suspended ones leave conversions `pending`.
- Self-referral (tenant email == affiliate email) → conversion `rejected`; coupon self-use blocked at apply time and at approval.
- Commission = `Affiliate::commissionFor(type, value, paidAmount)`; fixed commission is capped at the sale amount. The snapshotted rate is used, not the affiliate's current rate.
- Coupons: `validateCoupon` checks active window, affiliate active, `minimum_spend`, self-use; re-run in `RegisterPage::startPayment` so the price cannot be tampered with.
- Statuses: `pending → approved → paid`, or `rejected` / `refunded`.

## Payouts
- `issuePayout` locks the affiliate, enforces `amount <= balance` and the minimum payout, and allocates the amount oldest-first to approved conversions (`paid_amount`; `paid` once fully covered) so books always reconcile.
- Affiliates can request a payout (`affiliate_payout_requests`; capped by balance minus pending requests). Admin pays it from the Affiliates list (prefilled) or rejects it.

## Refunds / reversal
- `PaymentLogAffiliateObserver`: payment log → `Refunded` reverses its commission. Admin can also click **Reverse** on a conversion.
- Reversal debits `balance` and `total_earned` by the full commission. If part was already paid out, `balance` goes negative (clawback debt) and blocks payouts until future earnings offset it.

## Settings
`config/affiliate.php` defaults, overridden by `app_settings` keys `affiliate_*` via `App\Support\AffiliateSettings` (admin: Affiliates → Program Settings): attribution days, default commission for new affiliates, minimum payout.

## Tests
`tests/Feature/Affiliate/AffiliateServiceTest.php`.
