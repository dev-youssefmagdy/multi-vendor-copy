<?php

declare(strict_types=1);

namespace Tests\Feature\Affiliate;

use App\Enums\ActivationStatus;
use App\Livewire\Admin\Affiliate\PayoutsListPage;
use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\Affiliate;
use App\Models\AffiliatePayout;
use App\Services\AffiliateService;
use App\Support\AffiliateSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/** Withdrawal requests (affiliate portal / vendor Partner Program) must reach /admin/affiliates/payouts. */
class AdminAffiliatePayoutRequestsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): AdminUser
    {
        $role = AdminRole::query()->create([
            'name' => 'Role ' . uniqid(), 'permissions' => ['affiliates.manage'], 'permissions_count' => 1,
        ]);

        return AdminUser::query()->create([
            'role_id' => $role->id, 'name' => 'Admin', 'email' => uniqid('admin') . '@example.com',
            'password' => Hash::make('password12345'), 'status' => ActivationStatus::Active,
        ]);
    }

    private function affiliate(float $balance): Affiliate
    {
        return Affiliate::query()->create([
            'name' => 'Vendor Partner', 'email' => uniqid('aff') . '@example.com', 'password' => 'secret123',
            'code' => Affiliate::generateUniqueCode(), 'commission_type' => 'percentage', 'commission_value' => 10,
            'status' => 'active', 'balance' => $balance, 'total_earned' => $balance,
        ]);
    }

    public function test_default_minimum_withdrawal_is_50(): void
    {
        $this->assertSame(50.0, AffiliateSettings::minPayout());
    }

    public function test_request_below_minimum_is_rejected_and_cannot_be_requested(): void
    {
        $service = app(AffiliateService::class);
        $a = $this->affiliate(40);

        $this->assertFalse($service->canRequestPayout($a));
        $this->expectException(\InvalidArgumentException::class);
        $service->requestPayout($a, 40);
    }

    public function test_pending_request_is_listed_and_admin_can_mark_it_paid_with_receipt(): void
    {
        // A temp root instead of Storage::fake(): the shared storage/framework/testing dir may hold other users' files.
        $root = sys_get_temp_dir() . '/affiliate-payouts-' . uniqid();
        Storage::set('public', Storage::createLocalDriver(['root' => $root, 'url' => 'http://localhost/storage']));
        $a = $this->affiliate(120);
        $request = app(AffiliateService::class)->requestPayout($a, 80, 'IBAN please');

        $this->actingAs($this->admin(), 'admin')
            ->get('http://localhost/admin/affiliates/payouts')
            ->assertOk()->assertSee('Vendor Partner')->assertSee('80.00')->assertSee('IBAN please');

        Livewire::test(PayoutsListPage::class)
            ->call('openPayModal', $request->id)
            ->assertSet('payAmount', '80.00')
            ->call('markPaid')
            ->assertHasErrors(['payReceipt' => 'required'])
            ->set('payReceipt', UploadedFile::fake()->create('receipt.pdf', 20, 'application/pdf'))
            ->set('payReference', 'TRX-1')
            ->call('markPaid')
            ->assertHasNoErrors()
            ->assertSet('showPayModal', false);

        $request->refresh();
        $this->assertSame('fulfilled', $request->status);
        $payout = AffiliatePayout::query()->sole();
        $this->assertSame($payout->id, $request->affiliate_payout_id);
        $this->assertEquals(80.0, (float) $payout->amount);
        $this->assertSame('TRX-1', $payout->reference);
        $this->assertNotEmpty($payout->attachment_path);
        $this->assertCount(1, Storage::disk('public')->allFiles('affiliate-payouts'));
        $this->assertEquals(40.0, (float) $a->fresh()->balance);
    }

    public function test_admin_can_reject_a_request_with_a_note(): void
    {
        $a = $this->affiliate(100);
        $request = app(AffiliateService::class)->requestPayout($a, 60);

        $this->actingAs($this->admin(), 'admin');
        Livewire::test(PayoutsListPage::class)
            ->call('openRejectModal', $request->id)
            ->set('rejectNote', 'Bank details missing')
            ->call('rejectRequest')
            ->assertHasNoErrors();

        $request->refresh();
        $this->assertSame('rejected', $request->status);
        $this->assertSame('Bank details missing', $request->admin_note);
        $this->assertEquals(100.0, (float) $a->fresh()->balance);
        $this->assertSame(0, AffiliatePayout::query()->count());
    }
}
