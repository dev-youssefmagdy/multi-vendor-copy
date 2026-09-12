<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Helpers\TenantNavigation;
use App\Models\Tenant;
use App\Models\Tenant\AdminUser;
use App\Models\Tenant\Language;
use App\Models\Tenant\PaymentGateway;
use App\Models\Tenant\Setting;
use App\Models\Tenant\Theme;
use App\Repositories\Tenant\TenantPanelRepository;
use Illuminate\Support\Facades\Auth;

/**
 * Read helpers and mutating side-effects for the Onboarding page (tour +
 * setup checklist). Ported unchanged from `App\Livewire\Tenant\Onboarding\OnboardingPage`.
 */
class OnboardingService
{
    public function __construct(
        private readonly TenantPanelRepository $repo,
    ) {
    }

    // ── Tour steps data ─────────────────────────────────────────────────────

    public function steps(): array
    {
        return [
            [
                'title' => 'Welcome to Your Vendor Panel',
                'description' => 'This is your central command center for managing every aspect of your online store — products, orders, customers, analytics, and more. Let us walk you through the key sections.',
                'icon' => 'welcome',
                'color' => 'cyan',
            ],
            [
                'title' => 'Dashboard',
                'description' => 'The Dashboard gives you a real-time snapshot of your store\'s performance: total revenue, orders, customer growth, sales charts, and order status distribution — all in one view.',
                'icon' => 'dashboard',
                'color' => 'violet',
                'route_hint' => 'tenant.dashboard',
            ],
            [
                'title' => 'Catalog & Inventory',
                'description' => 'Manage your product catalog here. Add products, organize categories, set up product variants, and flag featured or new-in items. Own Products lets you create store-exclusive listings.',
                'icon' => 'products',
                'color' => 'cyan',
                'route_hint' => 'tenant.products.index',
            ],
            [
                'title' => 'Orders & Customers',
                'description' => 'Track every order from placement to delivery, update order statuses, and handle manufacturing requests. The Customers section shows purchase history, wallet balances, and lifetime value.',
                'icon' => 'orders',
                'color' => 'green',
                'route_hint' => 'tenant.orders.index',
            ],
            [
                'title' => 'Analytics & Insights',
                'description' => 'Dive deep into your business data with Order Analytics, Customer Lifetime Value, Shipping performance, and Product Profitability reports to make informed decisions.',
                'icon' => 'analytics',
                'color' => 'amber',
                'route_hint' => 'tenant.analytics.orders',
            ],
            [
                'title' => 'Finance — Wallet & Billing',
                'description' => 'Monitor your wallet balance, review transaction history, and keep track of subscription billing. Funds collected from orders are reflected here after platform processing.',
                'icon' => 'wallet',
                'color' => 'violet',
                'route_hint' => 'tenant.finance.wallet',
            ],
            [
                'title' => 'Storefront & Online Store',
                'description' => 'Control the customer-facing side of your business. Switch themes, create custom pages, manage flash sales, coupons, and configure your storefront\'s appearance including logo, banners, and social links.',
                'icon' => 'storefront',
                'color' => 'cyan',
                'route_hint' => 'tenant.store.appearance',
            ],
            [
                'title' => 'Settings & Configuration',
                'description' => 'Manage your store\'s regional settings (currencies, languages), access control (admins & roles), payment gateways, email templates, and mail configuration to keep everything running smoothly.',
                'icon' => 'settings',
                'color' => 'green',
                'route_hint' => 'tenant.settings.account',
            ],
            [
                'title' => 'You\'re All Set!',
                'description' => 'You now know your way around the vendor panel. Next, we\'ll help you complete a few quick setup tasks to get your store ready for customers. It only takes a minute.',
                'icon' => 'done',
                'color' => 'green',
            ],
        ];
    }

    // ── Setup status helpers ────────────────────────────────────────────────

    public function allItemsDone(): bool
    {
        return $this->logoIsSet()
            && $this->activeTheme() !== null
            && $this->paymentGatewayConfigured()
            && $this->languageConfigured()
            && TenantNavigation::profileComplete()
            && TenantNavigation::storeDetailsComplete()
            && TenantNavigation::complianceComplete();
    }

    public function logoIsSet(): bool
    {
        return TenantNavigation::logoIsConfigured();
    }

    public function activeTheme(): ?string
    {
        return Theme::query()
            ->where('is_active', true)
            ->value('name');
    }

    public function paymentGatewayConfigured(): bool
    {
        return PaymentGateway::query()->where('is_active', true)->whereNotNull('connection_status')->exists();
    }

    public function languageConfigured(): bool
    {
        return Language::query()->where('is_active', true)->count() > 0;
    }

    public function setupItems(): array
    {
        $logoSet = $this->logoIsSet();
        $activeTheme = $this->activeTheme();

        return [
            [
                'key' => 'logo',
                'label' => 'Set Up Your Store Logo',
                'detail' => 'Your brand logo appears on the storefront header and invoices.',
                'mandatory' => true,
                'done' => $logoSet,
                'action_url' => route('tenant.store.appearance'),
                'action_label' => 'Go to Appearance',
                'icon' => 'logo',
            ],
            [
                'key' => 'theme',
                'label' => 'Choose a Storefront Theme',
                'detail' => $activeTheme
                    ? 'Active theme: ' . $activeTheme . '. You can switch anytime.'
                    : 'Pick a theme to define the look and feel of your store.',
                'mandatory' => false,
                'done' => $activeTheme !== null,
                'action_url' => route('tenant.store.themes'),
                'action_label' => 'Browse Themes',
                'icon' => 'theme',
            ],
            [
                'key' => 'payment_gateway',
                'label' => 'Set Up a Payment Gateway',
                'detail' => $this->paymentGatewayConfigured()
                    ? 'At least one payment method is active on your store.'
                    : 'Enable a payment gateway so customers can complete purchases.',
                'mandatory' => false,
                'done' => $this->paymentGatewayConfigured(),
                'action_url' => route('tenant.settings.payment-gateways', ['from' => 'onboarding']),
                'action_label' => 'Configure Payments',
                'icon' => 'payment',
            ],
            [
                'key' => 'languages',
                'label' => 'Configure Store Languages',
                'detail' => $this->languageConfigured()
                    ? 'Multiple languages are active on your store.'
                    : 'Add more languages to reach a wider audience.',
                'mandatory' => false,
                'done' => $this->languageConfigured(),
                'action_url' => route('tenant.settings.languages'),
                'action_label' => 'Manage Languages',
                'icon' => 'languages',
            ],
            [
                'key' => 'profile',
                'label' => 'Complete Your Profile',
                'detail' => TenantNavigation::profileComplete()
                    ? 'Your business name, logo, and contact info are set.'
                    : 'Add your business name, logo, and contact info.',
                'mandatory' => true,
                'done' => TenantNavigation::profileComplete(),
                'action_url' => route('tenant.settings.account', ['from' => 'onboarding']),
                'action_label' => 'Go to Account Settings',
                'icon' => 'logo',
            ],
            [
                'key' => 'store_details',
                'label' => 'Complete Store Details',
                'detail' => TenantNavigation::storeDetailsComplete()
                    ? 'Your store name, description, and address are set.'
                    : 'Add your store name, description, and address.',
                'mandatory' => true,
                'done' => TenantNavigation::storeDetailsComplete(),
                'action_url' => route('tenant.settings.account', ['from' => 'onboarding']) . '#store-details',
                'action_label' => 'Go to Store Details',
                'icon' => 'storefront',
            ],
            [
                'key' => 'compliance',
                'label' => 'Complete Compliance Information',
                'detail' => TenantNavigation::complianceComplete()
                    ? 'Owner details, business registration, and bank info are on file.'
                    : 'Add owner details, business registration, and bank info in the Compliance Center.',
                'mandatory' => true,
                'done' => TenantNavigation::complianceComplete(),
                'action_url' => route('tenant.settings.compliance', ['from' => 'onboarding']),
                'action_label' => 'Go to Compliance Center',
                'icon' => 'settings',
            ],
        ];
    }

    public function paymentReadinessItems(): array
    {
        $items = $this->repo->paymentReadiness();

        return array_values(array_filter(
            $items,
            fn (array $item) => !str_contains(strtolower($item['label']), 'target currencies')
        ));
    }

    /** The 10 logo settings, with the same defaults the Livewire class used. */
    public function logoSettings(): array
    {
        $settings = Setting::query()
            ->whereIn('name', [
                'logo_mode',
                'logo_text_ar',
                'logo_text_en',
                'logo_color',
                'logo_bg_color',
                'logo_shape',
                'logo_font_ar',
                'logo_font_en',
                'logo_path_ar',
                'logo_path_en',
            ])
            ->pluck('value', 'name');

        return [
            'logo_mode' => ($settings['logo_mode'] ?? '') === 'text' ? 'text' : 'image',
            'logo_text_ar' => (string) ($settings['logo_text_ar'] ?? ''),
            'logo_text_en' => (string) ($settings['logo_text_en'] ?? ''),
            'logo_color' => ($settings['logo_color'] ?? '') ?: '#111827',
            'logo_bg_color' => ($settings['logo_bg_color'] ?? '') ?: '#ffffff',
            'logo_shape' => ($settings['logo_shape'] ?? '') === 'rounded' ? 'rounded' : 'rectangle',
            'logo_font_ar' => ($settings['logo_font_ar'] ?? '') ?: 'cairo',
            'logo_font_en' => ($settings['logo_font_en'] ?? '') ?: 'poppins',
            'logo_path_ar' => ($settings['logo_path_ar'] ?? '') ?: null,
            'logo_path_en' => ($settings['logo_path_en'] ?? '') ?: null,
        ];
    }

    public function paymentReadinessSkipped(): bool
    {
        /** @var AdminUser|null $admin */
        $admin = Auth::guard('tenant')->user();

        return $admin?->payment_readiness_skipped_at !== null;
    }

    // ── Mutating side-effects ────────────────────────────────────────────────

    public function markTourSeen(): void
    {
        /** @var AdminUser|null $admin */
        $admin = Auth::guard('tenant')->user();
        $admin?->forceFill(['tour_seen_at' => now()])->save();
    }

    public function skipPaymentReadiness(): void
    {
        /** @var AdminUser|null $admin */
        $admin = Auth::guard('tenant')->user();
        $admin?->forceFill(['payment_readiness_skipped_at' => now()])->save();
    }

    public function dismissSetup(): void
    {
        /** @var AdminUser|null $admin */
        $admin = Auth::guard('tenant')->user();
        $admin?->forceFill(['setup_dismissed_at' => now()])->save();

        // Permanently unlock the storefront so StoreLaunchGate passes immediately
        // on every future request without needing to re-check all 8 steps.
        $tenantId = tenant()?->getTenantKey();
        if ($tenantId) {
            Tenant::saveData($tenantId, ['launch_ready' => true]);
        }
    }
}
