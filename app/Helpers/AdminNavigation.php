<?php

namespace App\Helpers;

use App\Models\AdminUser;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class AdminNavigation
{
    public static function sections(): array
    {
        return [
            [
                'label' => 'Overview',
                'items' => [
                    [
                        'type' => 'link',
                        'label' => 'Dashboard',
                        'route' => 'admin.dashboard',
                        'icon' => 'dashboard',
                        'permission' => 'dashboard.view',
                    ],
                ],
            ],
            [
                'label' => 'Commerce',
                'items' => [
                    [
                        'type' => 'group',
                        'label' => 'Product Management',
                        'icon' => 'products',
                        'children' => [
                            ['label' => 'Products List', 'route' => 'admin.products.index', 'permission' => 'catalog.products.view'],
                            ['label' => 'Categories List', 'route' => 'admin.categories.index', 'permission' => 'catalog.categories.view'],
                            ['label' => 'Variations List', 'route' => 'admin.variations.index', 'permission' => 'catalog.variations.view'],
                            ['label' => 'New In Products', 'route' => 'admin.badges.new-in', 'permission' => 'catalog.badges.manage'],
                            ['label' => 'Best Selling Products', 'route' => 'admin.badges.best-selling', 'permission' => 'catalog.badges.manage'],
                            ['label' => 'Product Edit Requests', 'route' => 'admin.products.edit-requests', 'permission' => 'catalog.product-edit-requests.manage'],
                        ],
                    ],
                    [
                        'type' => 'group',
                        'label' => 'Orders',
                        'icon' => 'orders',
                        'children' => [
                            ['label' => 'Orders List', 'route' => 'admin.orders.index', 'permission' => 'sales.orders.view'],
                            ['label' => 'Orders Report', 'route' => 'admin.orders.report', 'permission' => 'sales.orders.report.view'],
                            ['label' => 'Return Requests', 'route' => 'admin.orders.returns.index', 'permission' => 'sales.orders.view'],
                        ],
                    ],
                    [
                        'type' => 'group',
                        'label' => 'Shipping Management',
                        'icon' => 'shipping',
                        'children' => [
                            ['label' => 'In Delivery Orders', 'route' => 'admin.shipping.in-delivery', 'permission' => 'shipping.delivery.view'],
                            ['label' => 'Fixed Shipping Costs', 'route' => 'admin.shipping.fixed-costs', 'permission' => 'shipping.delivery.view'],
                            // ['label' => 'Delivery Charges', 'route' => 'admin.shipping.delivery-charges', 'permission' => 'shipping.delivery.view'],
                            ['label' => 'Branches', 'route' => 'admin.branches.index', 'permission' => 'branches.view'],
                            ['label' => 'Shipping Settings', 'route' => 'admin.shipping.settings', 'permission' => 'shipping.settings.view'],
                            ['label' => 'Default Shipping Days', 'route' => 'admin.shipping.shipping-days.index', 'permission' => 'shipping.settings.view'],
                            ['label' => 'Delivery Popup Days', 'route' => 'admin.shipping.delivery-popup.index', 'permission' => 'shipping.settings.view'],
                        ],
                    ],
                    [
                        'type' => 'group',
                        'label' => 'Store',
                        'icon' => 'plans',
                        'children' => [
                            ['label' => 'Coupons', 'route' => 'admin.store.coupons.index', 'permission' => 'store.coupons.manage'],
                            ['label' => 'Flash Sales', 'route' => 'admin.store.flash-sales.index', 'permission' => 'store.flash-sales.manage'],
                            ['label' => 'Tenant Sync', 'route' => 'admin.store.tenant-sync.index', 'permission' => 'store.sync.manage'],
                        ],
                    ],
                    [
                        'type' => 'group',
                        'label' => 'Manufacturing',
                        'icon' => 'manufacturing',
                        'children' => [
                            ['label' => 'Manufacturing Requests', 'route' => 'admin.manufacturing.index', 'permission' => 'manufacturing.view'],
                        ],
                    ],
                    [
                        'type' => 'link',
                        'label' => 'Payment Logs',
                        'route' => 'admin.payment-logs.index',
                        'icon' => 'payments',
                        'permission' => 'billing.payment-logs.view',
                    ],
                    [
                        'type' => 'link',
                        'label' => 'Customers',
                        'route' => 'admin.customers.index',
                        'icon' => 'orders',
                        'permission' => 'sales.customers.view',
                    ],
                    [
                        'type' => 'group',
                        'label' => 'Plans',
                        'icon' => 'plans',
                        'children' => [
                            ['label' => 'Registered Users', 'route' => 'admin.plans.users', 'permission' => 'plans.tenants.view'],
                            ['label' => 'Pending Registrations', 'route' => 'admin.plans.pending-registrations', 'permission' => 'plans.pending-registrations.view'],
                            ['label' => 'Plans', 'route' => 'admin.plans.index', 'permission' => 'plans.packages.view'],
                        ],
                    ],
                    [
                        'type' => 'group',
                        'label' => 'Wallet Management',
                        'icon' => 'wallet',
                        'children' => [
                            ['label' => 'Tenants Ledger', 'route' => 'admin.wallets.index', 'permission' => 'billing.wallets.view'],
                            ['label' => 'Finance Reports', 'route' => 'admin.finance.reports', 'permission' => 'billing.reports.view'],
                            ['label' => 'Vendor Settlements', 'route' => 'admin.vendor-settlements.index', 'permission' => 'billing.vendor-settlements.view'],
                            ['label' => 'Tenant Payouts', 'route' => 'admin.finance.payouts.index', 'permission' => 'billing.payouts.manage'],
                            // ['label' => 'Transactions', 'route' => 'admin.wallet-transactions.index', 'permission' => 'billing.transactions.view'],
                            ['label' => 'Invoices', 'route' => 'admin.invoices.index', 'permission' => 'billing.invoices.view'],
                        ],
                    ],
                ],
            ],
            [
                'label' => 'Content',
                'items' => [
                    [
                        'type' => 'group',
                        'label' => 'Blog Management',
                        'icon' => 'blog',
                        'children' => [
                            ['label' => 'Categories', 'route' => 'admin.blog.categories', 'permission' => 'content.blog-categories.view'],
                            ['label' => 'Posts', 'route' => 'admin.blog.posts', 'permission' => 'content.blog-posts.view'],
                        ],
                    ],
                    [
                        'type' => 'group',
                        'label' => 'Domains',
                        'icon' => 'domains',
                        'anyPermission' => ['domains.requests.view', 'domains.dns.view'],
                        'children' => [
                            ['label' => 'Domain Requests', 'route' => 'admin.domains.requests', 'permission' => 'domains.requests.view'],
                            ['label' => 'DNS Records', 'route' => 'admin.domains.dns-records', 'permission' => 'domains.dns.view'],
                        ],
                    ],
                    [
                        'type' => 'link',
                        'label' => 'Static Pages',
                        'route' => 'admin.pages.index',
                        'icon' => 'pages',
                        'permission' => 'content.pages.view',
                    ],
                    [
                        'type' => 'link',
                        'label' => 'FAQs',
                        'route' => 'admin.faqs.index',
                        'icon' => 'faq',
                        'permission' => 'content.faqs.view',
                    ],
                    [
                        'type' => 'link',
                        'label' => 'Newsletter',
                        'route' => 'admin.newsletter.index',
                        'icon' => 'email',
                        'permission' => 'content.newsletter.view',
                    ],
                ],
            ],
            [
                'label' => 'System',
                'items' => [
                    [
                        'type' => 'group',
                        'label' => 'Settings',
                        'icon' => 'settings',
                        'children' => [
                            ['label' => 'General Settings', 'route' => 'admin.settings.general', 'permission' => 'settings.general.manage'],
                            ['label' => 'Theme Colors', 'route' => 'admin.settings.theme-colors', 'permission' => 'settings.theme-colors.manage'],
                            ['label' => 'Home Variants', 'route' => 'admin.settings.home-variants', 'permission' => 'settings.home-variants.manage'],
                            ['label' => 'Template Control', 'route' => 'admin.settings.templates', 'permission' => 'settings.templates.manage'],
                            ['label' => 'Custom Templates', 'route' => 'admin.settings.custom-templates', 'permission' => 'settings.templates.manage'],
                            ['label' => 'Payment Gateways', 'route' => 'admin.settings.payment-gateways', 'permission' => 'settings.payment-gateways.manage'],
                            ['label' => 'Payment Gateway Limits', 'route' => 'admin.settings.payment-gateway-limits', 'permission' => 'settings.payment-gateway-limits.manage'],
                            ['label' => 'Email Templates', 'route' => 'admin.settings.email-templates', 'permission' => 'settings.email-templates.manage'],
                            ['label' => 'Email Configuration', 'route' => 'admin.settings.email-configuration', 'permission' => 'settings.email-configuration.manage'],
                            ['label' => 'Currencies', 'route' => 'admin.settings.currencies', 'permission' => 'settings.currencies.view'],
                            ['label' => 'Languages', 'route' => 'admin.settings.languages', 'permission' => 'settings.languages.view'],
                            ['label' => 'Translation Key Access', 'route' => 'admin.settings.translation-key-access', 'permission' => 'settings.languages.manage'],
                            ['label' => 'Language Purchases', 'route' => 'admin.settings.language-purchases', 'permission' => 'settings.languages.purchases.view'],
                            ['label' => 'Translation Files', 'route' => 'admin.settings.translations', 'permission' => 'settings.languages.view'],
                            ['label' => 'Countries', 'route' => 'admin.settings.countries', 'permission' => 'settings.countries.view'],
                            ['label' => 'Maintenance Mode', 'route' => 'admin.settings.maintenance', 'permission' => 'settings.maintenance.manage'],
                            ['label' => 'Default Banners', 'route' => 'admin.settings.default-banners', 'permission' => 'settings.default-banners.manage'],
                        ],
                    ],
                    [
                        'type' => 'group',
                        'label' => 'Admins Management',
                        'icon' => 'admins',
                        'children' => [
                            ['label' => 'Admins List', 'route' => 'admin.admins.index', 'permission' => 'admins.users.view'],
                            ['label' => 'Role & Permissions', 'route' => 'admin.admins.roles-permissions', 'permission' => 'admins.roles.view'],
                        ],
                    ],
                    [
                        'type' => 'group',
                        'label' => 'Clear Cache',
                        'icon' => 'cache',
                        'children' => [
                            ['label' => 'Tenants Cache', 'route' => 'admin.cache.tenants', 'permission' => 'system.cache.manage'],
                            ['label' => 'Main Cache', 'route' => 'admin.cache.main', 'permission' => 'system.cache.manage'],
                        ],
                    ],
                    [
                        'type' => 'link',
                        'label' => 'Developer Docs',
                        'route' => 'admin.dev-docs.index',
                        'icon' => 'pages',
                        'permission' => 'system.dev-docs.view',
                    ],
                ],
            ],
        ];
    }

    public static function visibleSections(): array
    {
        $user = auth('admin')->user();

        return collect(self::sections())
            ->map(function (array $section) use ($user) {
                $items = collect($section['items'])
                    ->map(function (array $item) use ($user) {
                        if (($item['type'] ?? 'link') === 'link') {
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
        if ($routeName) {
            $routeGroups = [
                'admin.products.' => [
                    'section' => 'Commerce',
                    'group' => 'Product Management',
                    'labels' => [
                        'admin.products.create' => 'Add Product',
                        'admin.products.edit' => 'Edit Product',
                        'admin.products.edit-requests' => 'Product Edit Requests',
                    ],
                    'default' => 'Products List',
                ],
                'admin.categories.' => [
                    'section' => 'Commerce',
                    'group' => 'Product Management',
                    'labels' => [
                        'admin.categories.create' => 'Add Category',
                        'admin.categories.edit' => 'Edit Category',
                    ],
                    'default' => 'Categories List',
                ],
                'admin.variations.' => [
                    'section' => 'Commerce',
                    'group' => 'Product Management',
                    'labels' => [
                        'admin.variations.create' => 'Add Variation',
                        'admin.variations.edit' => 'Edit Variation',
                    ],
                    'default' => 'Variations List',
                ],
                'admin.badges.' => [
                    'section' => 'Commerce',
                    'group' => 'Product Management',
                    'labels' => [
                        'admin.badges.new-in' => 'New In Products',
                        'admin.badges.best-selling' => 'Best Selling Products',
                    ],
                    'default' => 'Product Badges',
                ],
                'admin.shipping.delivery-charges' => [
                    'section' => 'Commerce',
                    'group' => 'Shipping Management',
                    'labels' => [
                        'admin.shipping.delivery-charges.create' => 'Add Shipping Zone',
                        'admin.shipping.delivery-charges.edit' => 'Edit Shipping Zone',
                    ],
                    'default' => 'Delivery Charges',
                ],
                'admin.shipping.settings' => [
                    'section' => 'Commerce',
                    'group' => 'Shipping Management',
                    'labels' => [
                        'admin.shipping.settings' => 'Shipping Settings',
                    ],
                    'default' => 'Shipping Settings',
                ],
                'admin.store.' => [
                    'section' => 'Commerce',
                    'group' => 'Store',
                    'labels' => [
                        'admin.store.flash-sales.index' => 'Flash Sales',
                        'admin.store.tenant-sync.index' => 'Tenant Sync',
                    ],
                    'default' => 'Store',
                ],
                'admin.manufacturing.' => [
                    'section' => 'Commerce',
                    'group' => 'Manufacturing',
                    'labels' => [
                        'admin.manufacturing.index' => 'Manufacturing Requests',
                    ],
                    'default' => 'Manufacturing Requests',
                ],
                'admin.plans.users' => [
                    'section' => 'Commerce',
                    'group' => 'Plans',
                    'labels' => [
                        'admin.plans.users.create' => 'Add Tenant',
                        'admin.plans.users.edit' => 'Edit Tenant',
                    ],
                    'default' => 'Registered Users',
                ],
                'admin.plans.' => [
                    'section' => 'Commerce',
                    'group' => 'Plans',
                    'labels' => [
                        'admin.plans.create' => 'Add Package',
                        'admin.plans.edit' => 'Edit Package',
                    ],
                    'default' => 'Plans',
                ],
                'admin.settings.languages' => [
                    'section' => 'System',
                    'group' => 'Settings',
                    'labels' => [
                        'admin.settings.languages.create' => 'Add Language',
                        'admin.settings.languages.edit' => 'Edit Language',
                    ],
                    'default' => 'Languages',
                ],
                'admin.settings.currencies' => [
                    'section' => 'System',
                    'group' => 'Settings',
                    'labels' => [
                        'admin.settings.currencies.create' => 'Add Currency',
                        'admin.settings.currencies.edit' => 'Edit Currency',
                    ],
                    'default' => 'Currencies',
                ],
                'admin.orders.show' => [
                    'section' => 'Commerce',
                    'group' => 'Orders',
                    'labels' => ['admin.orders.show' => 'Order Details'],
                    'default' => 'Order Details',
                ],
                'admin.orders.returns.show' => [
                    'section' => 'Commerce',
                    'group' => 'Orders',
                    'labels' => ['admin.orders.returns.show' => 'Return Details'],
                    'default' => 'Return Details',
                ],
                'admin.shipping.in-delivery.show' => [
                    'section' => 'Commerce',
                    'group' => 'Shipping Management',
                    'labels' => ['admin.shipping.in-delivery.show' => 'Order Details'],
                    'default' => 'Order Details',
                ],
                'admin.payment-logs.show' => [
                    'section' => 'Commerce',
                    'group' => null,
                    'labels' => ['admin.payment-logs.show' => 'Payment Log Details'],
                    'default' => 'Payment Log Details',
                ],
                'admin.wallets.show' => [
                    'section' => 'Commerce',
                    'group' => 'Wallet Management',
                    'labels' => ['admin.wallets.show' => 'Tenant Ledger Details'],
                    'default' => 'Tenant Ledger Details',
                ],
                'admin.invoices.show' => [
                    'section' => 'Commerce',
                    'group' => 'Wallet Management',
                    'labels' => ['admin.invoices.show' => 'Invoice Details'],
                    'default' => 'Invoice Details',
                ],
            ];

            foreach ($routeGroups as $prefix => $config) {
                if (Str::startsWith($routeName, $prefix)) {
                    return [
                        'section' => $config['section'],
                        'group' => $config['group'],
                        'label' => $config['labels'][$routeName] ?? $config['default'],
                    ];
                }
            }
        }

        foreach (self::sections() as $section) {
            foreach ($section['items'] as $item) {
                if (($item['type'] ?? 'link') === 'link' && $item['route'] === $routeName) {
                    return [
                        'section' => $section['label'],
                        'group' => null,
                        'label' => $item['label'],
                    ];
                }

                foreach ($item['children'] ?? [] as $child) {
                    if ($child['route'] === $routeName) {
                        return [
                            'section' => $section['label'],
                            'group' => $item['label'],
                            'label' => $child['label'],
                        ];
                    }
                }
            }
        }

        return [
            'section' => 'Overview',
            'group' => null,
            'label' => 'Dashboard',
        ];
    }

    public static function groupIsActive(array $item, ?string $routeName): bool
    {
        foreach ($item['children'] ?? [] as $child) {
            if ($child['route'] === $routeName) {
                return true;
            }

            if ($routeName && Str::startsWith($routeName, Str::beforeLast($child['route'], '.') . '.')) {
                return true;
            }
        }

        return false;
    }

    public static function href(array $item): string
    {
        return Route::has($item['route']) ? route($item['route']) : '#';
    }

    public static function isActive(array $item, ?string $routeName): bool
    {
        if (!$routeName) {
            return false;
        }

        if ($item['route'] === $routeName) {
            return true;
        }

        // Match route family: admin.payment-logs.index → active on admin.payment-logs.*
        if (Str::endsWith($item['route'], '.index')) {
            $familyPrefix = Str::beforeLast($item['route'], '.index') . '.';
            return Str::startsWith($routeName, $familyPrefix);
        }

        return false;
    }

    protected static function canAccessItem(array $item, mixed $user): bool
    {
        if (!($user instanceof AdminUser)) {
            return false;
        }

        if (!empty($item['anyPermission'])) {
            return $user->hasAnyPermission((array) $item['anyPermission']);
        }

        $permission = $item['permission'] ?? null;

        if (!$permission) {
            return true;
        }

        return $user->hasPermission($permission);
    }
}
