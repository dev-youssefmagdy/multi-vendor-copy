<?php

namespace Tests\Feature\Affiliate;

use App\Enums\PaymentLogStatus;
use App\Http\Controllers\Tenant\Panel\Finance\PartnerWalletController;
use App\Http\Requests\Tenant\Panel\Finance\PartnerWithdrawRequest;
use App\Livewire\Affiliate\Auth\LoginPage;
use App\Models\Affiliate;
use App\Models\AffiliateReferral;
use App\Models\PaymentLog;
use App\Models\Tenant;
use App\Services\AffiliateService;
use App\Services\TenantAffiliateService;
use App\Support\AffiliateSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Tenant-as-affiliate. Tenants are inserted as raw rows (no tenant databases are created);
 * the tenant panel controller is exercised directly with tenancy()->tenant set manually,
 * since booting real tenancy would require a provisioned tenant DB.
 */
class TenantAffiliateTest extends TestCase
{
    use RefreshDatabase;

    private TenantAffiliateService $tenants;
    private AffiliateService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenants = app(TenantAffiliateService::class);
        $this->service = app(AffiliateService::class);
    }

    protected function tearDown(): void
    {
        tenancy()->tenant = null;
        parent::tearDown();
    }

    private function tenantRow(string $id, string $email = null): void
    {
        DB::table('tenants')->insert([
            'id' => $id, 'data' => json_encode(['email' => $email ?? "$id@example.com", 'shop_name' => "Shop $id"]),
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function external(array $o = []): Affiliate
    {
        return Affiliate::query()->create($o + [
            'name' => 'Ext', 'email' => 'ext' . uniqid() . '@example.com', 'password' => 'secret123',
            'code' => Affiliate::generateUniqueCode(), 'commission_type' => 'percentage',
            'commission_value' => 10, 'status' => 'active',
        ]);
    }

    private function payment(string $tenantId, float $amount = 100): PaymentLog
    {
        return PaymentLog::query()->create([
            'tenant_id' => $tenantId, 'gateway' => 'stripe', 'amount' => $amount,
            'status' => PaymentLogStatus::Paid, 'paid_at' => now(),
        ]);
    }

    public function test_for_tenant_is_idempotent_and_unique(): void
    {
        $this->tenantRow('shopa');
        $a = $this->tenants->forTenant('shopa');
        $b = $this->tenants->forTenant('shopa');
        $this->tenantRow('shopb');
        $c = $this->tenants->forTenant(Tenant::find('shopb'));

        $this->assertSame($a->id, $b->id);
        $this->assertNotSame($a->code, $c->code);
        $this->assertSame(2, Affiliate::where('type', 'tenant')->count());
        $this->assertTrue($a->isTenant());
        $this->assertSame('active', $a->status);
        $this->assertSame('shopa', $a->tenant_id);
    }

    public function test_external_scope_excludes_tenant_affiliates(): void
    {
        $this->tenantRow('shopa');
        $this->tenants->forTenant('shopa');
        $ext = $this->external();

        $this->assertEquals([$ext->id], Affiliate::external()->pluck('id')->all());
    }

    public function test_referral_url_uses_central_domain(): void
    {
        $this->tenantRow('shopa');
        $a = $this->tenants->forTenant('shopa');
        $url = $this->tenants->referralUrl($a);

        $this->assertStringContainsString((string) config('tenancy.central_domains.0'), $url);
        $this->assertStringEndsWith('/?ref=' . $a->code, $url);
        $this->assertStringStartsWith(parse_url(config('app.url'), PHP_URL_SCHEME) . '://', $url);
    }

    public function test_registration_creates_affiliate_with_owner_email_and_password_that_can_log_in(): void
    {
        $this->tenantRow('shopa'); // tenantRow() stores the owner email
        $email = Tenant::find('shopa')->email;

        $a = $this->tenants->forTenant('shopa', 'my-register-pass');

        $this->assertSame(strtolower($email), $a->email);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('my-register-pass', $a->password));

        // Same credential check the affiliate login page performs.
        $this->assertTrue(\Illuminate\Support\Facades\Auth::guard('affiliate')
            ->attempt(['email' => $a->email, 'password' => 'my-register-pass']));
        $this->assertFalse(\Illuminate\Support\Facades\Auth::guard('affiliate')
            ->attempt(['email' => $a->email, 'password' => 'wrong']));
    }

    public function test_existing_affiliate_email_gets_a_new_unique_variant_with_same_password(): void
    {
        $email = 'dup@example.com';
        $this->tenantRow('shopa', $email);
        $this->tenantRow('shopb', $email);
        $this->external(['email' => $email]);

        $a = $this->tenants->forTenant('shopa', 'pw-123456');
        $b = $this->tenants->forTenant('shopb', 'pw-123456');

        [$local, $domain] = explode('@', $email);
        $this->assertSame("{$local}+shopa@{$domain}", $a->email);
        $this->assertSame("{$local}+shopb@{$domain}", $b->email);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('pw-123456', $b->password));
        $this->assertSame(3, Affiliate::query()->count());
    }

    public function test_full_flow_credits_tenant_affiliate_balance_and_stats(): void
    {
        $this->tenantRow('referrer');
        $this->tenantRow('newbie');
        $a = $this->tenants->forTenant('referrer');

        AffiliateReferral::query()->create(['affiliate_id' => $a->id]); // visit only
        AffiliateReferral::query()->create(['affiliate_id' => $a->id]); // visit only
        $ref = AffiliateReferral::query()->create(['affiliate_id' => $a->id]);
        $this->service->markReferralConverted($ref, 'newbie');

        $s = $this->tenants->stats($a->fresh());
        $this->assertSame(3, $s['visits']);
        $this->assertSame(1, $s['registered']);
        $this->assertSame(0, $s['paying']);
        $this->assertSame(3, $s['visits_30d']);
        $this->assertSame(1, $s['registered_30d']);

        $conv = $this->service->approveConversion('newbie', $this->payment('newbie', 100));
        $this->assertSame('approved', $conv->status);

        $s = $this->tenants->stats($a->fresh());
        $this->assertSame(1, $s['paying']);
        $this->assertEquals(10.0, $s['balance']);
        $this->assertEquals(10.0, $s['total_earned']);
        $this->assertEquals(0.0, $s['total_paid']);
        $this->assertEquals(10.0, $s['requestable']);
        $this->assertEquals(AffiliateSettings::minPayout(), $s['min_payout']);
    }

    public function test_self_referral_by_same_tenant_id_or_same_owner_email_is_rejected(): void
    {
        // same tenant id
        $this->tenantRow('self', 'owner@example.com');
        $a = $this->tenants->forTenant('self');
        $ref = AffiliateReferral::query()->create(['affiliate_id' => $a->id]);
        $this->service->markReferralConverted($ref, 'self');
        $this->assertNull($this->service->approveConversion('self', $this->payment('self')));

        // different tenant, same owner email
        $this->tenantRow('second', 'OWNER@example.com');
        $ref2 = AffiliateReferral::query()->create(['affiliate_id' => $a->id]);
        $this->service->markReferralConverted($ref2, 'second');
        $this->assertNull($this->service->approveConversion('second', $this->payment('second')));

        $this->assertEquals(0.0, (float) $a->fresh()->balance);
    }

    private function fund(Affiliate $a, float $amount): void
    {
        $a->forceFill(['balance' => $amount, 'total_earned' => $amount])->save();
    }

    public function test_withdraw_request_validations(): void
    {
        $this->tenantRow('shopa');
        $a = $this->tenants->forTenant('shopa');
        $this->fund($a, 100);
        $min = AffiliateSettings::minPayout();

        if ($min > 0.01) {
            try {
                $this->service->requestPayout($a, $min - 0.01);
                $this->fail('below min accepted');
            } catch (\InvalidArgumentException $e) {
                $this->assertTrue(true);
            }
        }
        foreach ([0.0, -5.0, 100.01] as $bad) {
            try {
                $this->service->requestPayout($a, $bad);
                $this->fail("accepted $bad");
            } catch (\InvalidArgumentException $e) {
                $this->assertTrue(true);
            }
        }

        $this->service->requestPayout($a, 60);
        // pending cap: only 40 left requestable
        $this->assertEquals(40.0, $this->service->requestableAmount($a->fresh()));
        try {
            $this->service->requestPayout($a->fresh(), 50);
            $this->fail('exceeded pending cap');
        } catch (\InvalidArgumentException $e) {
            $this->assertTrue(true);
        }
        $this->service->requestPayout($a->fresh(), 40);
        $this->assertEquals(0.0, $this->service->requestableAmount($a->fresh()));
        $this->assertEquals(100.0, (float) $a->fresh()->balance); // balance untouched until admin payout
    }

    private function actAsTenant(string $id): PartnerWalletController
    {
        tenancy()->tenant = Tenant::find($id);
        $this->assertSame($id, tenant('id'));

        return app(PartnerWalletController::class);
    }

    private function withdrawRequest(array $data): PartnerWithdrawRequest
    {
        $req = PartnerWithdrawRequest::create('/wallet/partner/withdraw', 'POST', $data);
        $req->setContainer(app())->setRedirector(app('redirect'));
        $req->setLaravelSession(app('session.store'));
        $req->validateResolved();

        return $req;
    }

    public function test_partner_endpoints_are_isolated_per_tenant(): void
    {
        $this->tenantRow('shopa');
        $this->tenantRow('shopb');
        $this->tenantRow('buyera');
        $this->tenantRow('buyerb');
        $a = $this->tenants->forTenant('shopa');
        $b = $this->tenants->forTenant('shopb');
        foreach ([[$a, 'buyera'], [$b, 'buyerb']] as [$aff, $buyer]) {
            $ref = AffiliateReferral::query()->create(['affiliate_id' => $aff->id]);
            $this->service->markReferralConverted($ref, $buyer);
            $this->service->approveConversion($buyer, $this->payment($buyer, 100));
            $aff->refresh();
        }
        $this->service->requestPayout($a->fresh(), 5);
        $this->service->requestPayout($b->fresh(), 7);
        $this->service->requestPayout($b->fresh(), 2);

        $ctl = $this->actAsTenant('shopa');
        $conv = $ctl->conversionsData(request())->getData(true);
        $this->assertCount(1, $conv['data']);
        $this->assertStringStartsWith('bu', strip_tags($conv['data'][0]['merchant']));
        $pay = $ctl->payoutsData(request())->getData(true);
        $this->assertCount(1, $pay['data']);
        $this->assertStringContainsString('5.00', $pay['data'][0]['amount']);

        $ctl = $this->actAsTenant('shopb');
        $this->assertCount(1, $ctl->conversionsData(request())->getData(true)['data']);
        $this->assertCount(2, $ctl->payoutsData(request())->getData(true)['data']);
    }

    /** Known defect while PartnerWalletController::conversionsData returns all raw model columns. */
    public function test_conversions_data_does_not_leak_raw_merchant_id(): void
    {
        $this->tenantRow('shopa');
        $this->tenantRow('buyera');
        $a = $this->tenants->forTenant('shopa');
        $ref = AffiliateReferral::query()->create(['affiliate_id' => $a->id]);
        $this->service->markReferralConverted($ref, 'buyera');
        $this->service->approveConversion('buyera', $this->payment('buyera'));

        $json = json_encode($this->actAsTenant('shopa')->conversionsData(request())->getData(true)['data']);
        $this->assertStringNotContainsString('buyera', $json, 'raw tenant_id column leaks unmasked merchant id');
    }

    public function test_withdraw_action_success_and_errors(): void
    {
        $this->tenantRow('shopa');
        $a = $this->tenants->forTenant('shopa');
        $this->fund($a, 50);
        $ctl = $this->actAsTenant('shopa');

        $ok = $ctl->withdraw($this->withdrawRequest(['amount' => 20]));
        $this->assertSame(200, $ok->getStatusCode());
        $this->assertSame(1, $a->payoutRequests()->count());

        $bad = $ctl->withdraw($this->withdrawRequest(['amount' => 999]));
        $this->assertSame(422, $bad->getStatusCode());
        $this->assertSame(1, $a->payoutRequests()->count());

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->withdrawRequest(['amount' => -1]);
    }

    public function test_partner_routes_registered_with_csrf_protected_post(): void
    {
        $route = app('router')->getRoutes()->getByName('tenant.finance.wallet.partner.withdraw');
        $this->assertNotNull($route);
        $this->assertContains('POST', $route->methods());
        $this->assertNotContains('csrf_exempt', $route->gatherMiddleware());
        $this->assertNotNull(app('router')->getRoutes()->getByName('tenant.finance.wallet.partner.conversions.data'));
        $this->assertNotNull(app('router')->getRoutes()->getByName('tenant.finance.wallet.partner.payouts.data'));
    }

    public function test_sso_token_logs_in_once_and_expires(): void
    {
        $this->tenantRow('shopa');
        $a = $this->tenants->forTenant('shopa', 'pw-123456');

        $url   = $this->tenants->loginUrl($a);
        $token = basename($url);
        $this->assertStringContainsString((string) config('tenancy.central_domains.0'), $url);
        $this->assertStringContainsString('/affiliate/sso/', $url);

        $this->get('/affiliate/sso/' . $token)->assertRedirect(route('affiliate.dashboard'));
        $this->assertAuthenticatedAs($a, 'affiliate');

        // Token is single-use.
        auth('affiliate')->logout();
        $this->get('/affiliate/sso/' . $token)->assertRedirect(route('affiliate.login'));
        $this->assertGuest('affiliate');

        // Expired token.
        $old = basename($this->tenants->loginUrl($a));
        \App\Models\AffiliateLoginToken::query()->where('token_hash', hash('sha256', $old))->update(['expires_at' => now()->subSecond()]);
        $this->get('/affiliate/sso/' . $old)->assertRedirect(route('affiliate.login'));
        $this->assertGuest('affiliate');
    }

    public function test_sso_rejects_suspended_affiliate_and_unknown_token(): void
    {
        $this->tenantRow('shopa');
        $a = $this->tenants->forTenant('shopa');
        $token = basename($this->tenants->loginUrl($a));
        $a->update(['status' => 'suspended']);

        $this->get('/affiliate/sso/' . $token)->assertRedirect(route('affiliate.login'));
        $this->get('/affiliate/sso/' . str_repeat('x', 64))->assertRedirect(route('affiliate.login'));
        $this->assertGuest('affiliate');
    }

    public function test_commission_can_be_updated_per_tenant_affiliate_and_applies_to_new_conversions(): void
    {
        $this->tenantRow('shopa');
        $a = $this->tenants->forTenant('shopa');
        $a->update(['commission_type' => 'percentage', 'commission_value' => 25]);

        $referral = AffiliateReferral::query()->create(['affiliate_id' => $a->id]);
        $this->tenantRow('newshop', 'someone@example.com');
        app(AffiliateService::class)->markReferralConverted($referral, 'newshop');
        $c = app(AffiliateService::class)->approveConversion('newshop', $this->payment('newshop', 200));

        $this->assertEquals(50.00, (float) $c->commission_amount);
    }
}
