<?php

namespace App\Livewire\Tenant\Onboarding;

use App\Helpers\TenantNavigation;
use App\Models\Tenant\AdminUser;
use App\Models\Tenant\Language;
use App\Models\Tenant\PaymentGateway;
use App\Models\Tenant\Setting;
use App\Models\Tenant\Theme;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use App\Repositories\Tenant\StorefrontRepository;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Services\Tenant\TenantPanelService;

class OnboardingPage extends Component
{
    use WithFileUploads;

    public string $tab = 'tour';

    // ── Tour state ──────────────────────────────────────────────────────────
    public int $currentStep = 0;

    // ── Setup: logo builder ─────────────────────────────────────────────────
    public string $logoMode = 'image';
    public string $logoTextAr = '';
    public string $logoTextEn = '';
    public string $logoColor = '#111827';
    public string $logoBgColor = '#ffffff';
    public string $logoShape = 'rectangle';
    public string $logoFontAr = 'cairo';
    public string $logoFontEn = 'poppins';
    public ?string $logoPathAr = null;
    public ?string $logoPathEn = null;
    public $logoUploadAr = null;
    public $logoUploadEn = null;

    // ── Payment readiness ───────────────────────────────────────────────────
    public bool $paymentReadinessSkipped = false;

    public function mount(string $tab = 'tour'): void
    {
        $this->tab = in_array($tab, ['tour', 'setup']) ? $tab : 'tour';

        // Reaching the setup tab by any route (tab switch, direct link, sidebar)
        // means the forced first-run tour no longer needs to intercept navigation
        // away from this page — see OnboardingTour::maybeRedirectToOnboarding().
        if ($this->tab === 'setup') {
            $this->markTourSeen();
        }

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

        $this->logoMode = ($settings['logo_mode'] ?? '') === 'text' ? 'text' : 'image';
        $this->logoTextAr = (string) ($settings['logo_text_ar'] ?? '');
        $this->logoTextEn = (string) ($settings['logo_text_en'] ?? '');
        $this->logoColor = ($settings['logo_color'] ?? '') ?: '#111827';
        $this->logoBgColor = ($settings['logo_bg_color'] ?? '') ?: '#ffffff';
        $this->logoShape = ($settings['logo_shape'] ?? '') === 'rounded' ? 'rounded' : 'rectangle';
        $this->logoFontAr = ($settings['logo_font_ar'] ?? '') ?: 'cairo';
        $this->logoFontEn = ($settings['logo_font_en'] ?? '') ?: 'poppins';
        $this->logoPathAr = ($settings['logo_path_ar'] ?? '') ?: null;
        $this->logoPathEn = ($settings['logo_path_en'] ?? '') ?: null;

        /** @var AdminUser|null $admin */
        $admin = Auth::guard('tenant')->user();
        $this->paymentReadinessSkipped = $admin?->payment_readiness_skipped_at !== null;
    }

    public function goToTab(string $tab): void
    {
        $this->tab = in_array($tab, ['tour', 'setup']) ? $tab : 'tour';
        $this->redirectRoute('tenant.onboarding', ['tab' => $this->tab], navigate: true);
    }

    // ── Tour navigation ─────────────────────────────────────────────────────

    public function nextStep(): void
    {
        $steps = $this->steps();

        if ($this->currentStep < count($steps) - 1) {
            $this->currentStep++;
        } else {
            $this->completeTour();
        }
    }

    public function skipTour(): void
    {
        $this->markTourSeen();
        $this->goToTab('setup');
    }

    public function completeTour(): void
    {
        $this->markTourSeen();
        $this->goToTab('setup');
    }

    private function markTourSeen(): void
    {
        /** @var AdminUser|null $admin */
        $admin = Auth::guard('tenant')->user();
        $admin?->forceFill(['tour_seen_at' => now()])->save();
    }

    // ── Setup wizard actions ────────────────────────────────────────────────

    public function dismissSetup(): void
    {
        /** @var AdminUser|null $admin */
        $admin = Auth::guard('tenant')->user();
        $admin?->forceFill(['setup_dismissed_at' => now()])->save();

        // Permanently unlock the storefront so StoreLaunchGate passes immediately
        // on every future request without needing to re-check all 8 steps.
        $tenantId = tenant()?->getTenantKey();
        if ($tenantId) {
            \App\Models\Tenant::saveData($tenantId, ['launch_ready' => true]);
        }

        $this->redirectRoute('tenant.dashboard', navigate: true);
    }

    public function skipPaymentReadiness(): void
    {
        /** @var AdminUser|null $admin */
        $admin = Auth::guard('tenant')->user();
        $admin?->forceFill(['payment_readiness_skipped_at' => now()])->save();
        $this->paymentReadinessSkipped = true;
    }

    public function paymentReadinessItems(): array
    {
        $items = app(TenantPanelRepository::class)->paymentReadiness();

        return array_values(array_filter(
            $items,
            fn (array $item) => ($item['key'] ?? null) !== 'target_currencies'
        ));
    }

    public function saveLogo(TenantPanelService $service): void
    {
        $this->validate([
            'logoMode' => ['required', 'in:text,image'],
            'logoTextAr' => ['nullable', 'string', 'max:60'],
            'logoTextEn' => ['nullable', 'string', 'max:60'],
            'logoColor' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'logoBgColor' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'logoShape' => ['required', 'in:rectangle,rounded'],
            'logoFontAr' => ['required', 'in:' . implode(',', array_keys(StorefrontRepository::LOGO_FONTS['ar']))],
            'logoFontEn' => ['required', 'in:' . implode(',', array_keys(StorefrontRepository::LOGO_FONTS['en']))],
            'logoUploadAr' => ['nullable', 'image', 'max:2048'],
            'logoUploadEn' => ['nullable', 'image', 'max:2048'],
        ]);

        if ($this->logoUploadAr) {
            $this->logoPathAr = tenant_asset(\App\Support\ImageOptimizer::store($this->logoUploadAr, 'appearances/logos'));
        }
        if ($this->logoUploadEn) {
            $this->logoPathEn = tenant_asset(\App\Support\ImageOptimizer::store($this->logoUploadEn, 'appearances/logos'));
        }

        $service->saveAppearanceSettings([
            'logo_mode' => $this->logoMode,
            'logo_text_ar' => $this->logoTextAr,
            'logo_text_en' => $this->logoTextEn,
            'logo_color' => $this->logoColor,
            'logo_bg_color' => $this->logoBgColor,
            'logo_shape' => $this->logoShape,
            'logo_font_ar' => $this->logoFontAr,
            'logo_font_en' => $this->logoFontEn,
            'logo_path_ar' => $this->logoPathAr ?? '',
            'logo_path_en' => $this->logoPathEn ?? '',
        ]);

        $this->logoUploadAr = null;
        $this->logoUploadEn = null;
        $this->dispatch('admin-toast', message: __('Logo saved successfully.'), type: 'success');
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
                'label' => __('Set Up Your Store Logo'),
                'detail' => __('Your brand logo appears on the storefront header and invoices.'),
                'done' => $logoSet,
                'action_url' => route('tenant.store.appearance'),
                'action_label' => __('Go to Appearance'),
                'icon' => 'logo',
            ],
            [
                'key' => 'theme',
                'label' => __('Choose a Storefront Theme'),
                'detail' => $activeTheme
                    ? __('Active theme: :theme. You can switch anytime.', ['theme' => $activeTheme])
                    : __('Pick a theme to define the look and feel of your store.'),
                'done' => $activeTheme !== null,
                'action_url' => route('tenant.store.themes'),
                'action_label' => __('Browse Themes'),
                'icon' => 'theme',
            ],
            [
                'key' => 'payment_gateway',
                'label' => __('Set Up a Payment Gateway'),
                'detail' => $this->paymentGatewayConfigured()
                    ? __('At least one payment method is active on your store.')
                    : __('Enable a payment gateway so customers can complete purchases.'),
                'done' => $this->paymentGatewayConfigured(),
                'action_url' => route('tenant.settings.payment-gateways', ['from' => 'onboarding']),
                'action_label' => __('Configure Payments'),
                'icon' => 'payment',
            ],
            [
                'key' => 'languages',
                'label' => __('Configure Store Languages'),
                'detail' => $this->languageConfigured()
                    ? __('Multiple languages are active on your store.')
                    : __('Add more languages to reach a wider audience.'),
                'done' => $this->languageConfigured(),
                'action_url' => route('tenant.settings.languages'),
                'action_label' => __('Manage Languages'),
                'icon' => 'languages',
            ],
            [
                'key' => 'profile',
                'label' => __('Complete Your Profile'),
                'detail' => TenantNavigation::profileComplete()
                    ? __('Your business name, logo, and contact info are set.')
                    : __('Add your business name, logo, and contact info.'),
                'done' => TenantNavigation::profileComplete(),
                'action_url' => route('tenant.settings.account', ['from' => 'onboarding']),
                'action_label' => __('Go to Account Settings'),
                'icon' => 'logo',
            ],
            [
                'key' => 'store_details',
                'label' => __('Complete Store Details'),
                'detail' => TenantNavigation::storeDetailsComplete()
                    ? __('Your store name, description, and address are set.')
                    : __('Add your store name, description, and address.'),
                'done' => TenantNavigation::storeDetailsComplete(),
                'action_url' => route('tenant.settings.account', ['from' => 'onboarding']) . '#store-details',
                'action_label' => __('Go to Store Details'),
                'icon' => 'storefront',
            ],
            [
                'key' => 'compliance',
                'label' => __('Complete Compliance Information'),
                'detail' => TenantNavigation::complianceComplete()
                    ? __('Owner details, business registration, and bank info are on file.')
                    : __('Add owner details, business registration, and bank info in the Compliance Center.'),
                'done' => TenantNavigation::complianceComplete(),
                'action_url' => route('tenant.settings.compliance', ['from' => 'onboarding']),
                'action_label' => __('Go to Compliance Center'),
                'icon' => 'settings',
            ],
        ];
    }

    // ── Tour steps data ─────────────────────────────────────────────────────

    public function steps(): array
    {
        return [
            [
                'title' => __('Welcome to Your Vendor Panel'),
                'description' => __('This is your central command center for managing every aspect of your online store — products, orders, customers, analytics, and more. Let us walk you through the key sections.'),
                'icon' => 'welcome',
                'color' => 'cyan',
            ],
            [
                'title' => __('Dashboard'),
                'description' => __('The Dashboard gives you a real-time snapshot of your store\'s performance: total revenue, orders, customer growth, sales charts, and order status distribution — all in one view.'),
                'icon' => 'dashboard',
                'color' => 'violet',
                'route_hint' => 'tenant.dashboard',
            ],
            [
                'title' => __('Catalog & Inventory'),
                'description' => __('Manage your product catalog here. Add products, organize categories, set up product variants, and flag featured or new-in items. Own Products lets you create store-exclusive listings.'),
                'icon' => 'products',
                'color' => 'cyan',
                'route_hint' => 'tenant.products.index',
            ],
            [
                'title' => __('Orders & Customers'),
                'description' => __('Track every order from placement to delivery, update order statuses, and handle manufacturing requests. The Customers section shows purchase history, wallet balances, and lifetime value.'),
                'icon' => 'orders',
                'color' => 'green',
                'route_hint' => 'tenant.orders.index',
            ],
            [
                'title' => __('Analytics & Insights'),
                'description' => __('Dive deep into your business data with Order Analytics, Customer Lifetime Value, Shipping performance, and Product Profitability reports to make informed decisions.'),
                'icon' => 'analytics',
                'color' => 'amber',
                'route_hint' => 'tenant.analytics.orders',
            ],
            [
                'title' => __('Finance — Wallet & Billing'),
                'description' => __('Monitor your wallet balance, review transaction history, and keep track of subscription billing. Funds collected from orders are reflected here after platform processing.'),
                'icon' => 'wallet',
                'color' => 'violet',
                'route_hint' => 'tenant.finance.wallet',
            ],
            [
                'title' => __('Storefront & Online Store'),
                'description' => __('Control the customer-facing side of your business. Switch themes, create custom pages, manage flash sales, coupons, and configure your storefront\'s appearance including logo, banners, and social links.'),
                'icon' => 'storefront',
                'color' => 'cyan',
                'route_hint' => 'tenant.store.appearance',
            ],
            [
                'title' => __('Settings & Configuration'),
                'description' => __('Manage your store\'s regional settings (currencies, languages), access control (admins & roles), payment gateways, email templates, and mail configuration to keep everything running smoothly.'),
                'icon' => 'settings',
                'color' => 'green',
                'route_hint' => 'tenant.settings.account',
            ],
            [
                'title' => __('You\'re All Set!'),
                'description' => __('You now know your way around the vendor panel. Next, we\'ll help you complete a few quick setup tasks to get your store ready for customers. It only takes a minute.'),
                'icon' => 'done',
                'color' => 'green',
            ],
        ];
    }

    #[Layout('layouts.tenant')]
    public function render()
    {
        $steps = $this->steps();

        return view('livewire.tenant.onboarding.page', [
            'steps' => $steps,
            'setupItems' => $this->setupItems(),
            'totalSteps' => count($steps),
            'allItemsDone' => $this->allItemsDone(),
            'paymentReadinessItems' => $this->paymentReadinessItems(),
            'paymentReadinessSkipped' => $this->paymentReadinessSkipped,
        ]);
    }
}
