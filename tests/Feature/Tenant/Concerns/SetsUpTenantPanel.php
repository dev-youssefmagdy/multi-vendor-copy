<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant\Concerns;

use App\Enums\ActivationStatus;
use App\Enums\PaymentGatewayMode;
use App\Enums\PaymentGatewayType;
use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\Tenant\AdminRole;
use App\Models\Tenant\AdminUser;
use App\Models\Tenant\Language;
use App\Models\Tenant\PaymentGateway;
use App\Models\Tenant\Setting;
use App\Models\Tenant\Theme;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Hash;
use Stancl\Tenancy\Database\Models\Domain;

/**
 * Boots a real tenant (own SQLite database, created + migrated through the
 * normal stancl/tenancy TenantCreated job pipeline), seeds it with enough
 * data to satisfy TenantNavigation::onboardingSetupComplete() (so the global
 * EnforceOnboardingSetup / SetupGuard middleware never redirects us away),
 * and logs in a tenant admin holding every permission.
 */
trait SetsUpTenantPanel
{
    protected Tenant $tenant;
    protected Domain $domain;
    protected AdminUser $admin;
    protected AdminRole $ownerRole;
    protected Theme $activeTheme;
    protected PaymentGateway $activeGateway;
    protected Language $activeLanguage;
    protected string $tenantHost;
    protected string $plainPassword = 'password12345';

    protected function setUpTenantPanel(): void
    {
        $this->withoutMiddleware([VerifyCsrfToken::class, ValidateCsrfToken::class]);

        $this->tenantHost = 'panel-test-' . uniqid() . '.example.com';

        $this->tenant = Tenant::create([
            'status' => TenantStatus::Active->value,
            'shop_name' => 'Panel Test Store',
            'phone' => '+15551234567',
            'description' => 'A store used for automated panel tests.',
            'address' => '123 Test Ave, Test City',
            'launch_ready' => true,
        ]);

        $this->domain = $this->tenant->domains()->create([
            'domain' => $this->tenantHost,
        ]);

        tenancy()->initialize($this->tenant);

        try {
            $this->seedTenantData();
        } finally {
            tenancy()->end();
        }
    }

    protected function tearDownTenantPanel(): void
    {
        if (isset($this->tenant)) {
            tenancy()->end();
            $this->tenant->delete();
        }
    }

    protected function seedTenantData(): void
    {
        $allPermissions = array_keys(AdminRole::availablePermissions());

        $this->ownerRole = AdminRole::create([
            'name' => AdminRole::STORE_OWNER,
            'permissions' => $allPermissions,
            'permissions_count' => count($allPermissions),
        ]);

        $this->admin = AdminUser::create([
            'role_id' => $this->ownerRole->id,
            'name' => 'Panel Test Admin',
            'email' => 'admin@panel-test.example.com',
            'password' => Hash::make($this->plainPassword),
            'status' => ActivationStatus::Active,
            'email_verified_at' => now(),
        ]);

        $this->activeTheme = Theme::create([
            'name' => 'Test Theme',
            'slug' => 'test-theme',
            'is_active' => true,
            'is_universal' => true,
        ]);

        $this->activeGateway = PaymentGateway::create([
            'name' => 'Stripe',
            'code' => 'stripe',
            'type' => PaymentGatewayType::Orders->value,
            'is_active' => true,
            'use_own' => true,
            'is_primary' => true,
            'mode' => PaymentGatewayMode::Test->value,
            'connection_status' => 'connected',
            'required_keys' => ['public_key', 'secret_key'],
            'required_values' => ['public_key' => 'pk_test_x', 'secret_key' => 'sk_test_x'],
        ]);

        $this->activeLanguage = Language::create([
            'code' => 'en',
            'name' => 'English',
            'native_name' => 'English',
            'direction' => 'ltr',
            'is_active' => true,
            'is_default' => true,
            'sort_order' => 1,
        ]);

        // Logo configured (text mode).
        $this->setSetting('logo_mode', 'text');
        $this->setSetting('logo_text_en', 'Panel Test Store');
        $this->setSetting('logo_text_ar', 'متجر الاختبار');

        // Default pages reviewed.
        $this->setSetting('default_pages_reviewed_at', now()->toDateTimeString());

        // Compliance fields — all 14, so complianceCompletionPercent() === 100.
        $compliance = [
            'compliance_business_name' => 'Panel Test Business',
            'compliance_store_name' => 'Panel Test Store',
            'compliance_country' => 'US',
            'compliance_city' => 'Test City',
            'compliance_phone' => '+15551234567',
            'compliance_email' => 'owner@panel-test.example.com',
            'compliance_owner_name' => 'Jane Owner',
            'compliance_owner_id_number' => 'ID-123456',
            'compliance_registration_number' => 'REG-123456',
            'compliance_bank_name' => 'Test Bank',
            'compliance_bank_holder_name' => 'Jane Owner',
            'compliance_bank_account_number' => '000123456789',
            'compliance_bank_iban' => 'US00TEST0000000000',
            'compliance_doc_national_id_path' => 'compliance/national-id.pdf',
        ];

        foreach ($compliance as $name => $value) {
            $this->setSetting($name, $value);
        }
    }

    protected function setSetting(string $name, mixed $value): Setting
    {
        return Setting::create([
            'name' => $name,
            'value' => $value,
            'type' => \App\Enums\Tenant\SettingType::String->value,
            'group' => 'general',
        ]);
    }

    /** Prefix a panel-relative path with the tenant's test host. */
    protected function tenantUrl(string $path): string
    {
        $path = '/' . ltrim($path, '/');

        return 'http://' . $this->tenantHost . $path;
    }

    protected function actingAsTenantAdmin(): static
    {
        $this->actingAs($this->admin, 'tenant');

        return $this;
    }
}
