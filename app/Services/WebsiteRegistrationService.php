<?php

namespace App\Services;

use App\Enums\PackageStatus;
use App\Enums\PaymentLogStatus;
use App\Enums\Tenant\SubscriptionGatewayType;
use App\Enums\Tenant\SubscriptionStatus;
use App\Enums\TenantStatus;
use App\Enums\WalletTransactionDirection;
use App\Models\DomainRequest;
use App\Models\Package;
use App\Models\PaymentGateway;
use App\Models\PaymentLog;
use App\Models\Tenant;
use App\Models\Tenant\Subscription;
use App\Models\TenantCountry;
use App\Models\TenantOwner;
use App\Models\Tenant\Transaction as TenantTransaction;
use App\Models\Language;
use App\Services\AdminNotificationService;
use App\Services\Mail\TemplateMailService;
use Illuminate\Support\Str;
use Stancl\Tenancy\Database\Models\Domain;

class WebsiteRegistrationService
{
    public function __construct(
        protected TenantService $tenantService,
        protected TemplateMailService $templateMailService,
        protected AdminNotificationService $adminNotifier,
    ) {
    }

    public function centralDomain(): string
    {
        return (string) (config('tenancy.central_domains.0')
            ?: parse_url((string) config('app.url'), PHP_URL_HOST)
            ?: 'localhost');
    }

    public function normalizeDomain(string $domain): string
    {
        $domain = strtolower(trim($domain));
        $domain = preg_replace('#^https?://#', '', $domain) ?? $domain;

        return rtrim($domain, '/');
    }

    public function uniqueTenantSlug(string $shopName): string
    {
        $base = Str::slug($shopName);
        $base = $base !== '' ? $base : 'store';
        $candidate = $base;
        $counter = 2;

        while (Tenant::query()->where('data->slug', $candidate)->exists()) {
            $candidate = $base . '-' . $counter;
            $counter++;
        }

        return $candidate;
    }

    public function uniqueTenantDomain(string $slug, string $centralDomain): string
    {
        $base = strtolower($slug . '.' . $centralDomain);
        $candidate = $base;
        $counter = 2;

        while (
            Domain::query()->where('domain', $candidate)->exists()
            || DomainRequest::query()->where('domain', $candidate)->exists()
        ) {
            $candidate = strtolower($slug . '-' . $counter . '.' . $centralDomain);
            $counter++;
        }

        return $candidate;
    }

    public function finalize(array $registrationData, ?array $payment = null): Tenant
    {
        $centralDomain = $this->centralDomain();
        $slug = $this->uniqueTenantSlug((string) $registrationData['shop_name']);
        $subdomain = $this->uniqueTenantDomain($slug, $centralDomain);

        $customDomain = ($registrationData['domain_type'] ?? 'subdomain') === 'custom'
            ? $this->normalizeDomain((string) ($registrationData['custom_domain'] ?? ''))
            : null;

        $package = filled($registrationData['package_id'] ?? null)
            ? Package::query()
                ->where('id', (int) $registrationData['package_id'])
                ->where('status', PackageStatus::Published->value)
                ->firstOrFail()
            : null;

        $locale = filled($registrationData['locale'] ?? null) ? (string) $registrationData['locale'] : null;
        $language = $locale ? Language::query()->where('code', $locale)->where('is_active', true)->first() : null;

        $tenantAttributes = [
            'name' => (string) $registrationData['shop_name'],
            'slug' => $slug,
            'email' => (string) $registrationData['email'],
            'phone' => filled($registrationData['phone'] ?? null) ? (string) $registrationData['phone'] : null,
            'status' => TenantStatus::Active->value,
            'category_ids' => $registrationData['category_ids'] ?? null,
            'package_id' => $package?->id,
            'primary_language_id' => $language?->id,
            'trial_ends_at' => $package && $package->trial_days > 0 ? now()->addDays($package->trial_days) : null,
            'shop_name' => (string) $registrationData['shop_name'],
            'domain' => $customDomain ?: $subdomain,
            'profit_percentage' => (float) ($registrationData['profit_percentage'] ?? 0),
            'password' => (string) $registrationData['password'],
        ];

        $tenant = $this->tenantService->save($tenantAttributes);

        if (filled($registrationData['affiliate_referral_id'] ?? null)) {
            $referral = \App\Models\AffiliateReferral::query()->find((int) $registrationData['affiliate_referral_id']);

            if ($referral && !$referral->converted_at) {
                app(AffiliateService::class)->markReferralConverted($referral, $tenant->id);
            }
        }

        foreach ((array) ($registrationData['country_ids'] ?? []) as $countryId) {
            TenantCountry::query()->updateOrCreate(
                ['tenant_id' => $tenant->id, 'country_id' => (int) $countryId],
                ['is_active' => true]
            );
        }

        if ($package && $payment && (float) ($payment['amount'] ?? 0) > 0) {
            $paymentLog = $this->createCentralPaymentLog($tenant, $package, $payment);
            $this->createTenantBillingRecords($tenant, $package, $payment);

            if ($paymentLog) {
                $this->templateMailService->sendAdminSubscriptionActivated($tenant, $package, $paymentLog);
                $this->templateMailService->sendTenantSubscriptionActivated($tenant, $package, $paymentLog, $locale);

                $affiliateCouponId = (int) ($payment['applied_affiliate_coupon_id'] ?? 0);
                $affiliateCoupon   = null;
                $couponAffiliateId = null;

                if ($affiliateCouponId) {
                    $affiliateCoupon   = \App\Models\AffiliateCoupon::query()->with('affiliate')->find($affiliateCouponId);
                    $couponAffiliateId = $affiliateCoupon?->affiliate_id;
                }

                // URL-referral commission — suppressed when an affiliate coupon was used
                app(AffiliateService::class)->approveConversion($tenant->id, $paymentLog, $couponAffiliateId);

                // Affiliate coupon commission — fires only when an affiliate coupon was applied
                if ($affiliateCoupon) {
                    app(AffiliateService::class)->approveCouponConversion($tenant->id, $paymentLog, $affiliateCoupon);
                }
            }
        } else {
            // Free plan: create the tenant admin synchronously in the HTTP request,
            // matching the paid plan's synchronous tenant initialisation in createTenantBillingRecords().
            $this->tenantService->syncTenantOwnerAdmin($tenant, $tenantAttributes);
        }

        // When the tenant registered with a custom external domain, create a pending
        // DomainRequest so the central admin team can review and connect it.
        if ($customDomain) {
            DomainRequest::create([
                'tenant_id' => $tenant->id,
                'domain' => $customDomain,
                'status' => \App\Enums\DomainRequestStatus::Pending->value,
                'requested_at' => now(),
            ]);
        }

        TenantOwner::updateOrCreate(
            ['email' => strtolower(trim((string) $registrationData['email']))],
            array_filter([
                'tenant_id' => $tenant->id,
                'password' => bcrypt((string) $registrationData['password']),
                'provider' => $registrationData['provider'] ?? null,
                'provider_id' => $registrationData['provider_id'] ?? null,
                'avatar' => $registrationData['avatar'] ?? null,
            ], fn ($value) => $value !== null)
        );

        $tenant = $tenant->fresh(['domains', 'package.translations.language']);

        $subdomainUrl = 'https://' . $subdomain;
        $adminLoginUrl = 'https://' . $subdomain . '/admin/login';
        $storeUrl = $customDomain ? 'https://' . $customDomain : $subdomainUrl;
        $this->templateMailService->sendTenantWelcome($tenant, $storeUrl, $adminLoginUrl, $customDomain ? $subdomainUrl : null, $locale);

        $this->adminNotifier->notify(
            type: 'tenant',
            title: 'New Tenant Registered',
            message: sprintf('A new store "%s" has registered and is awaiting activation.', $tenant->shop_name ?? $tenant->id),
            data: ['tenant_id' => $tenant->getTenantKey(), 'email' => $tenant->email ?? null],
        );

        return $tenant;
    }

    protected function createCentralPaymentLog(Tenant $tenant, Package $package, array $payment): PaymentLog
    {
        return PaymentLog::create([
            'tenant_id' => $tenant->id,
            'package_id' => $package->id,
            'gateway' => (string) $payment['gateway_code'],
            'amount' => (float) $payment['amount'],
            'status' => PaymentLogStatus::Paid,
            'reference' => (string) ($payment['transaction_id'] ?? ''),
            'paid_at' => now(),
            'meta' => [
                'raw' => $this->normalizeRawResponse($payment['raw_response'] ?? null),
            ],
        ]);
    }

    protected function createTenantBillingRecords(Tenant $tenant, Package $package, array $payment): void
    {
        $gateway = PaymentGateway::query()->where('code', $payment['gateway_code'])->first();

        tenancy()->initialize($tenant);

        try {
            $transaction = TenantTransaction::query()->create([
                'uuid' => (string) Str::uuid(),
                'amount' => (float) $payment['amount'],
                'type' => WalletTransactionDirection::Credit->value,
                'admin_profit' => null,
                'description' => sprintf(
                    'Subscription payment for %s via %s',
                    $package->name,
                    str((string) $payment['gateway_code'])->replace(['_', '-'], ' ')->headline()->toString()
                ),
            ]);

            $subscription = Subscription::query()->create([
                'package_id' => $package->id,
                'price' => (float) $payment['amount'],
                'status' => SubscriptionStatus::Active->value,
                'term' => $package->term?->value ?? $package->term,
                'payment_gateway_type' => SubscriptionGatewayType::Central->value,
                'payment_gateway_id' => $gateway?->id,
                'transaction_id' => $transaction->id,
                'start_date' => now(),
                'end_date' => $this->resolveSubscriptionEndDate($package),
            ]);

            $transaction->update([
                'model_type' => Subscription::class,
                'model_id' => $subscription->id,
            ]);
        } finally {
            tenancy()->end();
        }
    }

    protected function resolveSubscriptionEndDate(Package $package): ?\DateTimeInterface
    {
        return match ($package->term?->value ?? $package->term) {
            'monthly' => now()->addMonth(),
            'quarterly' => now()->addMonths(3),
            'yearly' => now()->addYear(),
            default => null,
        };
    }

    protected function normalizeRawResponse(mixed $raw): mixed
    {
        if (!is_string($raw) || trim($raw) === '') {
            return $raw;
        }

        $decoded = json_decode($raw, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $raw;
    }
}
