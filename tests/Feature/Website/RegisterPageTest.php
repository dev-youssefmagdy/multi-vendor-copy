<?php

namespace Tests\Feature\Website;

use App\Enums\PackageStatus;
use App\Enums\PackageTerm;
use App\Livewire\Website\RegisterPage;
use App\Models\Package;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RegisterPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_restores_pending_paid_registration_on_payment_step(): void
    {
        $package = Package::query()->create([
            'name' => 'Growth',
            'status' => PackageStatus::Published->value,
            'term' => PackageTerm::Monthly->value,
            'price' => 49,
            'features' => [],
            'trial_days' => 0,
            'sort_order' => 1,
        ]);

        session()->put('website.register.pending', [
            'id' => 'reg-test-1',
            'data' => [
                'name' => 'Jane Vendor',
                'email' => 'jane@example.com',
                'password' => 'secret123',
                'password_confirmation' => 'secret123',
                'phone' => '+123456789',
                'shop_name' => 'Jane Store',
                'catalog_id' => null,
                'profit_percentage' => 15,
                'domain_type' => 'custom',
                'custom_domain' => 'janestore.com',
                'package_id' => $package->id,
                'gateway_code' => 'stripe',
                'package_price' => 49,
            ],
        ]);

        Livewire::test(RegisterPage::class)
            ->assertSet('step', 5)
            ->assertSet('name', 'Jane Vendor')
            ->assertSet('email', 'jane@example.com')
            ->assertSet('password', 'secret123')
            ->assertSet('passwordConfirmation', 'secret123')
            ->assertSet('shopName', 'Jane Store')
            ->assertSet('customDomain', 'janestore.com')
            ->assertSet('packageId', (string) $package->id)
            ->assertSet('gatewayCode', 'stripe');
    }
}
