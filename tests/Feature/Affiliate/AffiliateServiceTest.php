<?php

namespace Tests\Feature\Affiliate;

use App\Enums\PaymentLogStatus;
use App\Models\Affiliate;
use App\Models\AffiliateConversion;
use App\Models\AffiliateCoupon;
use App\Models\AffiliateReferral;
use App\Models\PaymentLog;
use App\Services\AffiliateService;
use App\Support\AffiliateSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AffiliateServiceTest extends TestCase
{
    use RefreshDatabase;

    private AffiliateService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AffiliateService::class);
    }

    private function affiliate(array $overrides = []): Affiliate
    {
        return Affiliate::query()->create($overrides + [
            'name' => 'Aff', 'email' => 'aff' . uniqid() . '@example.com', 'password' => 'secret123',
            'code' => Affiliate::generateUniqueCode(), 'commission_type' => 'percentage',
            'commission_value' => 10, 'status' => 'active',
        ]);
    }

    private function signup(Affiliate $affiliate, string $tenantId = 't1', ?string $tenantEmail = null): void
    {
        DB::table('tenants')->insert([
            'id' => $tenantId, 'data' => json_encode(['email' => $tenantEmail ?? 'owner@example.com']),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $referral = AffiliateReferral::query()->create(['affiliate_id' => $affiliate->id]);
        $this->service->markReferralConverted($referral, $tenantId);
    }

    private function payment(string $tenantId = 't1', float $amount = 100): PaymentLog
    {
        return PaymentLog::query()->create([
            'tenant_id' => $tenantId, 'gateway' => 'stripe', 'amount' => $amount,
            'status' => PaymentLogStatus::Paid, 'paid_at' => now(),
        ]);
    }

    public function test_paid_signup_approves_commission_once(): void
    {
        $a = $this->affiliate();
        $this->signup($a);
        $log = $this->payment();

        $this->assertSame('approved', $this->service->approveConversion('t1', $log)->status);
        $this->assertNull($this->service->approveConversion('t1', $log)); // idempotent

        $a->refresh();
        $this->assertEquals(10.00, (float) $a->balance);
        $this->assertEquals(10.00, (float) $a->total_earned);
    }

    public function test_commission_uses_rate_snapshot_and_fixed_is_capped(): void
    {
        $a = $this->affiliate(['commission_type' => 'fixed', 'commission_value' => 500]);
        $this->signup($a);
        $a->update(['commission_type' => 'percentage', 'commission_value' => 50]);

        $c = $this->service->approveConversion('t1', $this->payment(amount: 30));

        $this->assertEquals(30.00, (float) $c->commission_amount); // fixed 500 capped at sale amount
    }

    public function test_suspended_affiliate_earns_nothing_and_conversion_stays_pending(): void
    {
        $a = $this->affiliate();
        $this->signup($a);
        $a->update(['status' => 'suspended']);

        $this->assertNull($this->service->approveConversion('t1', $this->payment()));
        $this->assertSame('pending', AffiliateConversion::query()->first()->status);
        $this->assertEquals(0.0, (float) $a->fresh()->balance);
    }

    public function test_self_referral_is_rejected(): void
    {
        $a = $this->affiliate(['email' => 'me@example.com']);
        $this->signup($a, 't1', 'ME@example.com');

        $this->assertNull($this->service->approveConversion('t1', $this->payment()));
        $this->assertSame('rejected', AffiliateConversion::query()->first()->status);
        $this->assertEquals(0.0, (float) $a->fresh()->balance);
    }

    public function test_referral_converts_only_once(): void
    {
        $a = $this->affiliate();
        $referral = AffiliateReferral::query()->create(['affiliate_id' => $a->id]);

        $this->service->markReferralConverted($referral, 'tA');
        $this->service->markReferralConverted($referral, 'tB');

        $this->assertSame(1, AffiliateConversion::query()->count());
        $this->assertSame('tA', $referral->fresh()->tenant_id);
    }

    public function test_coupon_conversion_upgrades_existing_without_double_credit(): void
    {
        $a = $this->affiliate();
        $this->signup($a);
        $coupon = AffiliateCoupon::query()->create([
            'affiliate_id' => $a->id, 'code' => 'SAVE', 'discount_type' => 'percentage',
            'discount_value' => 20, 'commission_value' => 15,
        ]);

        $c = $this->service->approveCouponConversion('t1', $this->payment(amount: 100), $coupon);

        $this->assertSame(1, AffiliateConversion::query()->count());
        $this->assertEquals(15.00, (float) $c->commission_amount);
        $this->assertEquals(15.00, (float) $a->fresh()->balance);
    }

    public function test_coupon_validation_checks_affiliate_minimum_spend_and_self_use(): void
    {
        $a = $this->affiliate(['email' => 'me@example.com']);
        AffiliateCoupon::query()->create([
            'affiliate_id' => $a->id, 'code' => 'SAVE', 'discount_type' => 'percentage',
            'discount_value' => 20, 'minimum_spend' => 50,
        ]);

        $this->assertNotNull($this->service->validateCoupon('save', 100, 'x@example.com')[0]);
        $this->assertNull($this->service->validateCoupon('save', 10, 'x@example.com')[0]);
        $this->assertNull($this->service->validateCoupon('save', 100, 'ME@example.com')[0]);

        $a->update(['status' => 'suspended']);
        $this->assertNull($this->service->validateCoupon('save', 100, 'x@example.com')[0]);
    }

    public function test_payout_allocates_partially_and_reconciles(): void
    {
        $a = $this->affiliate();
        $this->signup($a, 't1');
        $this->signup($a, 't2');
        $this->service->approveConversion('t1', $this->payment('t1', 100)); // 10
        $this->service->approveConversion('t2', $this->payment('t2', 100)); // 10

        $this->service->issuePayout($a, ['amount' => 15]);

        $a->refresh();
        $this->assertEquals(5.00, (float) $a->balance);
        $this->assertEquals(15.00, (float) $a->total_paid);
        $this->assertEquals(
            15.00,
            (float) AffiliateConversion::query()->sum('paid_amount')
        );
        $this->assertSame(1, AffiliateConversion::query()->where('status', 'paid')->count());
        $this->assertSame(1, AffiliateConversion::query()->where('status', 'approved')->count());
    }

    public function test_payout_cannot_exceed_balance_or_fall_below_minimum(): void
    {
        $a = $this->affiliate();
        $this->signup($a);
        $this->service->approveConversion('t1', $this->payment()); // balance 10

        AffiliateSettings::save(['min_payout' => 5]);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->issuePayout($a, ['amount' => 11]);
    }

    public function test_payout_below_minimum_is_rejected(): void
    {
        $a = $this->affiliate(['balance' => 50, 'total_earned' => 50]);
        AffiliateSettings::save(['min_payout' => 20]);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->issuePayout($a, ['amount' => 5]);
    }

    public function test_payout_request_flow(): void
    {
        $a = $this->affiliate(['balance' => 40, 'total_earned' => 40]);
        AffiliateSettings::save(['min_payout' => 10]);

        $request = $this->service->requestPayout($a, 25);
        $this->assertEquals(15.0, $this->service->requestableAmount($a->fresh()));

        try {
            $this->service->requestPayout($a->fresh(), 20); // only 15 left to request
            $this->fail('Expected exception');
        } catch (\InvalidArgumentException) {
        }

        $this->service->issuePayout($a, ['amount' => 25, 'request_id' => $request->id]);
        $this->assertSame('fulfilled', $request->fresh()->status);
    }

    public function test_refund_reverses_commission_and_can_leave_clawback_debt(): void
    {
        $a = $this->affiliate();
        $this->signup($a);
        $log = $this->payment();
        $this->service->approveConversion('t1', $log);
        $this->service->issuePayout($a, ['amount' => 10]); // fully paid out

        $log->update(['status' => PaymentLogStatus::Refunded]); // observer fires

        $a->refresh();
        $this->assertSame('refunded', AffiliateConversion::query()->first()->status);
        $this->assertEquals(-10.00, (float) $a->balance);
        $this->assertEquals(0.00, (float) $a->total_earned);
    }

    public function test_settings_fall_back_to_config_and_can_be_overridden(): void
    {
        $this->assertSame(90, AffiliateSettings::cookieDays());

        AffiliateSettings::save(['cookie_days' => 30, 'default_commission_value' => 12.5]);

        $this->assertSame(30, AffiliateSettings::cookieDays());
        $this->assertSame(12.5, AffiliateSettings::defaultCommissionValue());
    }
}
