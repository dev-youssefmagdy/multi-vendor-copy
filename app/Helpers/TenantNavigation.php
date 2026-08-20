<?php

namespace App\Helpers;

use App\Enums\TenantStatus;
use App\Models\Tenant\AdminUser;
use App\Models\Tenant\Category;
use App\Models\Tenant\Language;
use App\Models\Tenant\PaymentGateway;
use App\Models\Tenant\Product;
use App\Models\Tenant\Setting;
use App\Models\Tenant\Theme;
use App\Models\TenantCountry;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class TenantNavigation
{
    public static function sections(): array
    {
        return [
            [
                'label' => 'Overview',
                'items' => [
                    ['type' => 'external', 'label' => 'Website URL', 'url' => self::storefrontUrl(), 'icon' => 'storefront', 'permission' => null],
                    ['type' => 'link', 'label' => 'Dashboard', 'route' => 'tenant.dashboard', 'icon' => 'dashboard', 'permission' => 'dashboard.view'],
                    ['type' => 'link', 'label' => 'Get Started', 'route' => 'tenant.onboarding', 'routeParameters' => ['tab' => 'tour'], 'icon' => 'settings', 'permission' => null],
                ],
            ],
            [
                'label' => 'Catalog',
                'items' => [
                    [
                        'type' => 'group',
                        'label' => 'Inventory',
                        'icon' => 'products',
                        'children' => [
                            ['label' => 'Products', 'route' => 'tenant.products.index', 'permission' => 'catalog.products.manage'],
                            ['label' => 'Own Products', 'route' => 'tenant.own-products.index', 'permission' => 'catalog.products.manage'],
                            ['label' => 'Categories', 'route' => 'tenant.categories.index', 'permission' => 'catalog.categories.manage'],
                            ['label' => 'New In Products', 'route' => 'tenant.badges.show', 'routeParameters' => ['badge' => 'new-in'], 'permission' => 'catalog.badges.manage'],
                            ['label' => 'Best Selling Products', 'route' => 'tenant.badges.show', 'routeParameters' => ['badge' => 'best-selling'], 'permission' => 'catalog.badges.manage'],
                            ['label' => 'Featured Products', 'route' => 'tenant.badges.show', 'routeParameters' => ['badge' => 'featured'], 'permission' => 'catalog.badges.manage'],
                            ['label' => 'Recommended Products', 'route' => 'tenant.badges.show', 'routeParameters' => ['badge' => 'recommended'], 'permission' => 'catalog.badges.manage'],
                        ]
                    ],
                    ['type' => 'link', 'label' => 'Orders', 'route' => 'tenant.orders.index', 'icon' => 'orders', 'permission' => 'sales.orders.view'],
                    ['type' => 'link', 'label' => 'Returns', 'route' => 'tenant.returns.index', 'icon' => 'orders', 'permission' => 'sales.returns.manage'],
                    ['type' => 'link', 'label' => 'Return Analytics', 'route' => 'tenant.returns.analytics', 'icon' => 'dashboard', 'permission' => 'sales.returns.manage'],
                    ['type' => 'link', 'label' => 'Customers', 'route' => 'tenant.customers.index', 'icon' => 'admins', 'permission' => 'sales.customers.manage'],
                    ['type' => 'link', 'label' => 'Manufacturing Requests', 'route' => 'tenant.manufacturing.index', 'icon' => 'manufacturing', 'permission' => 'catalog.products.manage'],
                    ['type' => 'link', 'label' => 'Notifications', 'route' => 'tenant.notifications.index', 'icon' => 'notifications', 'permission' => 'dashboard.view'],
                ],
            ],
            [
                'label' => 'Insights',
                'items' => [
                    [
                        'type' => 'group',
                        'label' => 'Analytics',
                        'icon' => 'dashboard',
                        'children' => [
                            ['label' => 'Order Analytics', 'route' => 'tenant.analytics.orders', 'permission' => 'analytics.view'],
                            ['label' => 'Customer Lifetime Value', 'route' => 'tenant.analytics.clv', 'permission' => 'analytics.view'],
                            ['label' => 'Shipping Analytics', 'route' => 'tenant.analytics.shipping', 'permission' => 'analytics.view'],
                            ['label' => 'Product Profitability', 'route' => 'tenant.analytics.profitability', 'permission' => 'analytics.view'],
                        ]
                    ],
                    [
                        'type' => 'group',
                        'label' => 'Finance',
                        'icon' => 'wallet',
                        'children' => [
                            ['label' => 'Wallet', 'route' => 'tenant.finance.wallet', 'permission' => 'finance.wallet.view'],
                            ['label' => 'Billing', 'route' => 'tenant.finance.billing', 'permission' => 'finance.billing.view'],
                            ['label' => 'Vendor Purchases', 'route' => 'tenant.finance.vendor-purchases', 'permission' => 'finance.vendor-purchases.view'],
                            ['label' => 'Settlement Payments', 'route' => 'tenant.finance.settlement-payments', 'permission' => 'finance.vendor-purchases.view'],
                            ['label' => 'Payouts Received', 'route' => 'tenant.finance.payouts', 'permission' => 'finance.wallet.view'],
                            // ['label' => 'Buy Languages', 'route' => 'tenant.finance.buy-languages', 'permission' => 'settings.languages.purchase'],
                        ]
                    ],
                ],
            ],
            [
                'label' => 'Storefront',
                'items' => [
                    [
                        'type' => 'group',
                        'label' => 'Online Store',
                        'icon' => 'pages',
                        'children' => [
                            ['label' => 'Themes', 'route' => 'tenant.store.themes', 'permission' => 'store.themes.manage'],
                            ['label' => 'Page Builder', 'route' => 'tenant.store.page-builder', 'permission' => 'store.page-builder.manage'],
                            ['label' => 'Pages', 'route' => 'tenant.store.pages', 'permission' => 'store.pages.manage'],
                        ]
                    ],
                    ['type' => 'link', 'label' => 'Coupons', 'route' => 'tenant.store.coupons', 'icon' => 'payments', 'permission' => 'store.coupons.manage'],
                    ['type' => 'link', 'label' => 'Flash Sales', 'route' => 'tenant.store.flash-sales', 'icon' => 'plans', 'permission' => 'store.flash-sales.manage'],
                    ['type' => 'link', 'label' => 'Appearance', 'route' => 'tenant.store.appearance', 'icon' => 'appearance', 'permission' => 'store.appearance.manage'],
                    ['type' => 'link', 'label' => 'Custom Template', 'route' => 'tenant.store.custom-template', 'icon' => 'appearance', 'permission' => 'store.custom-template.manage'],
                    // Subscribers page hidden from navbar
                    // ['type' => 'link', 'label' => 'Subscribers', 'route' => 'tenant.settings.subscribers', 'icon' => 'blog', 'permission' => 'store.subscribers.manage'],
                ],
            ],
            [
                'label' => 'Settings',
                'items' => [
                    [
                        'type' => 'group',
                        'label' => 'Regional',
                        'icon' => 'settings',
                        'children' => [
                            ['label' => 'Currencies', 'route' => 'tenant.settings.currencies', 'permission' => 'settings.regional.manage'],
                            // ['label' => 'Languages', 'route' => 'tenant.settings.languages', 'permission' => 'settings.regional.manage'],
                            ['label' => 'Languages Manage', 'route' => 'tenant.settings.languages-manage', 'permission' => 'settings.regional.manage'],
                            ['label' => 'Translations', 'route' => 'tenant.settings.translations', 'permission' => 'settings.translations.manage'],
                        ]
                    ],
                    [
                        'type' => 'group',
                        'label' => 'Access',
                        'icon' => 'admins',
                        'children' => [
                            ['label' => 'Tenant Admins', 'route' => 'tenant.settings.admins', 'permission' => 'settings.admins.manage'],
                            ['label' => 'Roles & Permissions', 'route' => 'tenant.settings.roles-permissions', 'permission' => 'settings.roles.manage'],
                        ]
                    ],
                    [
                        'type' => 'group',
                        'label' => 'Configuration',
                        'icon' => 'settings',
                        'children' => [
                            ['label' => 'Payment Gateways', 'route' => 'tenant.settings.payment-gateways', 'permission' => 'settings.payment-gateways.manage'],
                            ['label' => 'Return Policy', 'route' => 'tenant.settings.return-policy', 'permission' => 'sales.returns.manage'],
                            ['label' => 'Email Templates', 'route' => 'tenant.settings.email-templates', 'permission' => 'settings.mail.manage'],
                            ['label' => 'Mail Configurations', 'route' => 'tenant.settings.mail', 'permission' => 'settings.mail.manage'],
                            ['label' => 'Domains', 'route' => 'tenant.settings.domains', 'permission' => 'settings.domains.manage'],
                            ['label' => 'Tracking', 'route' => 'tenant.settings.tracking', 'permission' => 'settings.tracking.manage'],
                            ['label' => 'Account Settings', 'route' => 'tenant.settings.account', 'permission' => 'settings.account.manage'],
                            ['label' => 'General Settings', 'route' => 'tenant.settings.general', 'permission' => 'settings.account.manage'],
                            ['label' => 'Compliance Center', 'route' => 'tenant.settings.compliance', 'permission' => 'settings.account.manage'],
                        ]
                    ],
                ],
            ],
        ];
    }

    public static function visibleSections(): array
    {
        $user = auth('tenant')->user();

        return collect(self::sections())
            ->map(function (array $section) use ($user) {
                $items = collect($section['items'])
                    ->map(function (array $item) use ($user) {
                        if (in_array(($item['type'] ?? 'link'), ['link', 'external'])) {
                            return self::canAccessItem($item, $user) ? $item : null;
                        }

                        $children = collect($item['children'] ?? [])
                            ->filter(fn(array $child) => self::canAccessItem($child, $user))
                            ->values()
                            ->all();

                        if ($children === []) {
                            return null;
                        }

                        $item['children'] = $children;

                        return $item;
                    })
                    ->filter()
                    ->values()
                    ->all();

                $section['items'] = $items;

                return $section;
            })
            ->filter(fn(array $section) => $section['items'] !== [])
            ->values()
            ->all();
    }

    public static function routeInfo(?string $routeName): array
    {
        foreach (self::sections() as $section) {
            foreach ($section['items'] as $item) {
                if (($item['type'] ?? 'link') === 'link') {
                    if (self::itemMatchesRoute($item, $routeName) || self::routeMatchesFamily($item['route'], $routeName)) {
                        return ['section' => $section['label'], 'group' => null, 'label' => $item['label']];
                    }
                    continue;
                }

                foreach ($item['children'] ?? [] as $child) {
                    if (self::itemMatchesRoute($child, $routeName) || self::routeMatchesFamily($child['route'], $routeName)) {
                        return ['section' => $section['label'], 'group' => $item['label'], 'label' => $child['label']];
                    }
                }
            }
        }

        return ['section' => 'Overview', 'group' => null, 'label' => 'Dashboard'];
    }

    public static function groupIsActive(array $item, ?string $routeName): bool
    {
        foreach ($item['children'] ?? [] as $child) {
            if (self::itemMatchesRoute($child, $routeName)) {
                return true;
            }

            if (self::routeMatchesFamily($child['route'], $routeName)) {
                return true;
            }
        }

        return false;
    }

    /** Store-setup checklist progress, shown as a "x/7" badge on the Get Started nav item. */
    public static function onboardingSetupProgress(): array
    {
        $done = 0;
        $total = 7;

        if (self::logoIsConfigured()) {
            $done++;
        }
        if (Theme::query()->where('is_active', true)->exists()) {
            $done++;
        }
        if (PaymentGateway::query()->where('is_active', true)->where('use_own', true)->exists()) {
            $done++;
        }
        if (Language::query()->where('is_active', true)->exists()) {
            $done++;
        }
        if (self::profileComplete()) {
            $done++;
        }
        if (self::storeDetailsComplete()) {
            $done++;
        }
        if (self::complianceComplete()) {
            $done++;
        }

        return ['done' => $done, 'total' => $total];
    }

    /** Profile step: business name, logo, and a contact phone number set. */
    public static function profileComplete(): bool
    {
        $tenant = tenant();

        return self::logoIsConfigured()
            && filled($tenant?->phone)
            && filled($tenant?->shop_name);
    }

    /** Store-details step: store name, description, and address set. */
    public static function storeDetailsComplete(): bool
    {
        $tenant = tenant();

        return filled($tenant?->shop_name)
            && filled($tenant?->description)
            && filled($tenant?->address);
    }

    /** Compliance step: owner details, business registration, and bank info set. */
    public static function complianceComplete(): bool
    {
        return self::complianceCompletionPercent() === 100;
    }

    /**
     * Weighted percentage across the Compliance Center's required fields, used both
     * for the Account Setup Progress "compliance info" step and the admin overview.
     * Reads from the tenant's own `settings` table (group = 'compliance'); the
     * caller is responsible for tenancy being initialized for the tenant in question.
     */
    public static function complianceCompletionPercent(mixed $tenant = null): int
    {
        $fields = [
            'compliance_business_name',
            'compliance_store_name',
            'compliance_country',
            'compliance_city',
            'compliance_phone',
            'compliance_email',
            'compliance_owner_name',
            'compliance_owner_id_number',
            'compliance_registration_number',
            'compliance_bank_name',
            'compliance_bank_holder_name',
            'compliance_bank_account_number',
            'compliance_bank_iban',
            'compliance_doc_national_id_path',
        ];

        $values = Setting::query()
            ->whereIn('name', $fields)
            ->pluck('value', 'name')
            ->all();

        $filledCount = 0;
        foreach ($fields as $field) {
            if (filled($values[$field] ?? null)) {
                $filledCount++;
            }
        }

        return (int) round(($filledCount / count($fields)) * 100);
    }

    /**
     * The 10-step "Account Setup Progress" checklist (Prompt 33), each step
     * worth an equal 10% share of the persistent progress widget.
     */
    public static function setupProgress(): array
    {
        $steps = [
            [
                'key' => 'email_verified',
                'label' => 'Email verified',
                'done' => self::emailVerified(),
                'action_label' => 'Resend verification email',
                'action_route' => 'tenant.dashboard',
            ],
            [
                'key' => 'store_name_logo',
                'label' => 'Store name and logo set',
                'done' => self::profileComplete(),
                'action_label' => 'Go to Account Settings',
                'action_route' => 'tenant.settings.account',
            ],
            [
                'key' => 'category_selected',
                'label' => 'At least one category selected',
                'done' => self::categorySelected(),
                'action_label' => 'Manage Categories',
                'action_route' => 'tenant.categories.index',
            ],
            [
                'key' => 'product_synced',
                'label' => 'At least one product synced',
                'done' => self::productSynced(),
                'action_label' => 'Manage Products',
                'action_route' => 'tenant.products.index',
            ],
            [
                'key' => 'theme_selected',
                'label' => 'Theme selected',
                'done' => self::themeSelected(),
                'action_label' => 'Browse Themes',
                'action_route' => 'tenant.store.themes',
            ],
            [
                'key' => 'payment_gateway',
                'label' => 'Payment gateway configured',
                'done' => self::paymentGatewayIsConfigured(),
                'action_label' => 'Configure Payments',
                'action_route' => 'tenant.settings.payment-gateways',
            ],
            [
                'key' => 'target_countries',
                'label' => 'Target countries set',
                'done' => self::targetCountriesSet(),
                'action_label' => 'Manage Countries',
                'action_route' => 'tenant.settings.currencies',
            ],
            [
                'key' => 'compliance_info',
                'label' => 'Compliance info completed',
                'done' => self::complianceComplete(),
                'action_label' => 'Go to Compliance Center',
                'action_route' => 'tenant.settings.compliance',
            ],
            [
                'key' => 'default_pages_reviewed',
                'label' => 'Default pages reviewed/edited',
                'done' => self::defaultPagesReviewed(),
                'action_label' => 'Review Pages',
                'action_route' => 'tenant.store.pages',
            ],
            [
                'key' => 'storefront_launched',
                'label' => 'Storefront launched',
                'done' => self::storefrontLaunched(),
                'action_label' => 'Go to Dashboard',
                'action_route' => 'tenant.dashboard',
            ],
        ];

        $done = collect($steps)->filter(fn(array $step) => $step['done'])->count();
        $total = count($steps);

        foreach ($steps as &$step) {
            $step['action_url'] = Route::has($step['action_route']) ? route($step['action_route']) : '#';
        }
        unset($step);

        return [
            'steps' => $steps,
            'done' => $done,
            'total' => $total,
            'percent' => (int) round(($done / $total) * 100),
        ];
    }

    public static function emailVerified(): bool
    {
        $admin = auth('tenant')->user();

        return $admin instanceof AdminUser && $admin->hasVerifiedEmail();
    }

    public static function categorySelected(): bool
    {
        return Category::query()->exists();
    }

    public static function productSynced(): bool
    {
        return Product::query()->exists();
    }

    public static function themeSelected(): bool
    {
        return Theme::query()->where('is_active', true)->exists();
    }

    public static function paymentGatewayIsConfigured(): bool
    {
        return PaymentGateway::query()->where('is_active', true)->whereNotNull('connection_status')->exists();
    }

    public static function targetCountriesSet(): bool
    {
        $tenantId = tenant('id');

        if (!$tenantId) {
            return false;
        }

        return TenantCountry::query()->where('tenant_id', $tenantId)->where('is_active', true)->exists();
    }

    public static function defaultPagesReviewed(): bool
    {
        return filled(Setting::query()->where('name', 'default_pages_reviewed_at')->value('value'));
    }

    public static function storefrontLaunched(): bool
    {
        return tenant()?->status === TenantStatus::Active;
    }

    /** A logo counts as configured once a text wordmark is chosen or any image is uploaded. */
    public static function logoIsConfigured(): bool
    {
        $settings = Setting::query()
            ->whereIn('name', ['logo_mode', 'logo_text_ar', 'logo_text_en', 'logo_path_ar', 'logo_path_en'])
            ->pluck('value', 'name');

        if (($settings['logo_mode'] ?? '') === 'text') {
            return !empty($settings['logo_text_ar']) || !empty($settings['logo_text_en']);
        }

        return !empty($settings['logo_path_ar']) || !empty($settings['logo_path_en']);
    }

    public static function href(array $item): string
    {
        if (($item['type'] ?? 'link') === 'external') {
            return $item['url'] ?: '#';
        }
        return Route::has($item['route']) ? route($item['route'], $item['routeParameters'] ?? []) : '#';
    }

    public static function isActive(array $item, ?string $routeName): bool
    {
        return self::itemMatchesRoute($item, $routeName) || self::routeMatchesFamily($item['route'] ?? '', $routeName);
    }

    protected static function storefrontUrl(): string
    {
        $domain = tenant()?->domains()->first()?->domain;
        if (!$domain) {
            return '#';
        }
        return (str_starts_with($domain, 'http') ? '' : 'https://') . $domain;
    }

    protected static function canAccessItem(array $item, mixed $user): bool
    {
        $permission = $item['permission'] ?? null;

        if (!$permission) {
            return true;
        }

        return $user instanceof AdminUser && $user->hasPermission($permission);
    }

    protected static function routeMatchesFamily(string $baseRoute, ?string $currentRoute): bool
    {
        if (!$currentRoute) {
            return false;
        }

        $familyPrefix = Str::endsWith($baseRoute, '.index')
            ? Str::beforeLast($baseRoute, '.index') . '.'
            : $baseRoute . '.';

        return Str::startsWith($currentRoute, $familyPrefix);
    }

    protected static function itemMatchesRoute(array $item, ?string $routeName): bool
    {
        if (($item['route'] ?? null) !== $routeName) {
            return false;
        }

        foreach (($item['routeParameters'] ?? []) as $key => $value) {
            $current = request()->route($key);

            if (is_object($current) && method_exists($current, 'getRouteKey')) {
                $current = $current->getRouteKey();
            }

            if ((string) $current !== (string) $value) {
                return false;
            }
        }

        return true;
    }
}
