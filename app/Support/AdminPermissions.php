<?php

namespace App\Support;

class AdminPermissions
{
    public static function definitions(): array
    {
        return [
            'dashboard.view' => 'View dashboard',

            'catalog.products.view' => 'View products',
            'catalog.products.manage' => 'Manage products',
            'catalog.categories.view' => 'View categories',
            'catalog.categories.manage' => 'Manage categories',
            'catalog.variations.view' => 'View variations',
            'catalog.variations.manage' => 'Manage variations',
            'catalog.badges.manage' => 'Manage product badges',
            'catalog.product-edit-requests.manage' => 'Manage product edit requests',

            'sales.orders.view' => 'View orders',
            'sales.orders.manage' => 'Manage orders',
            'sales.orders.report.view' => 'View order reports',
            'shipping.delivery.view' => 'View shipping delivery',
            'shipping.delivery.manage' => 'Manage shipping delivery',
            'shipping.settings.view' => 'View shipping settings',
            'shipping.settings.manage' => 'Manage shipping settings',
            'store.coupons.manage' => 'Manage central coupons',
            'store.flash-sales.manage' => 'Manage central flash sales',
            'store.sync.manage' => 'Sync sections to tenants',
            'manufacturing.view' => 'View manufacturing requests',
            'manufacturing.manage' => 'Manage manufacturing requests',
            'branches.view' => 'View branches',
            'branches.manage' => 'Manage branches',

            'billing.payment-logs.view' => 'View payment logs',
            'billing.vendor-settlements.view' => 'View vendor settlements',
            'billing.wallets.view' => 'View tenants ledger',
            'billing.transactions.view' => 'View wallet transactions',
            'billing.invoices.view' => 'View invoices',
            'billing.reports.view' => 'View finance reports',
            'billing.payouts.manage' => 'Manage tenant payouts',
            'system.dev-docs.view' => 'View developer documentation',

            'plans.packages.view' => 'View packages',
            'plans.packages.manage' => 'Manage packages',
            'plans.tenants.view' => 'View tenants',
            'plans.tenants.manage' => 'Manage tenants',
            'plans.pending-registrations.view' => 'View pending registrations',
            'plans.pending-registrations.manage' => 'Manage pending registrations',

            'content.blog-categories.view' => 'View blog categories',
            'content.blog-categories.manage' => 'Manage blog categories',
            'content.blog-posts.view' => 'View blog posts',
            'content.blog-posts.manage' => 'Manage blog posts',
            'content.pages.view' => 'View static pages',
            'content.pages.manage' => 'Manage static pages',
            'content.faqs.view' => 'View FAQs',
            'content.faqs.manage' => 'Manage FAQs',
            'content.newsletter.view' => 'View newsletter subscribers',
            'content.newsletter.manage' => 'Manage newsletter subscribers',
            'domains.requests.view' => 'View domain requests',
            'domains.requests.manage' => 'Manage domain requests',
            'domains.dns.view' => 'View DNS records',
            'domains.dns.manage' => 'Manage DNS records',

            'settings.general.manage' => 'Manage general settings',
            'settings.theme-colors.manage' => 'Manage theme color defaults',
            'settings.home-variants.manage' => 'Manage home page variants',
            'settings.templates.manage' => 'Manage templates',
            'settings.payment-gateways.manage' => 'Manage payment gateways',
            'settings.payment-gateway-limits.manage' => 'Manage payment gateway limits',
            'settings.email-templates.manage' => 'Manage email templates',
            'settings.email-configuration.manage' => 'Manage email configuration',
            'settings.currencies.view' => 'View currencies',
            'settings.currencies.manage' => 'Manage currencies',
            'settings.languages.view' => 'View languages',
            'settings.languages.purchases.view' => 'View language purchases',
            'settings.languages.manage' => 'Manage languages',
            'settings.countries.view' => 'View countries',
            'settings.countries.manage' => 'Manage countries',
            'settings.maintenance.manage' => 'Manage maintenance mode',
            'settings.default-banners.manage' => 'Manage default banners',
            'settings.ai-logo-limits.manage' => 'Manage AI logo generation limits',

            'admins.users.view' => 'View admin users',
            'admins.users.manage' => 'Manage admin users',
            'admins.roles.view' => 'View roles and permissions',
            'admins.roles.manage' => 'Manage roles and permissions',

            'system.cache.manage' => 'Manage cache tools',
        ];
    }

    public static function all(): array
    {
        return array_keys(self::definitions());
    }

    public static function normalize(array $permissions, ?string $roleName = null): array
    {
        if ($roleName === 'Super Admin') {
            return self::all();
        }

        $normalized = [];

        foreach (array_values(array_unique(array_filter($permissions))) as $permission) {
            $mappedPermissions = self::legacyMap()[$permission] ?? [$permission];

            foreach ($mappedPermissions as $mappedPermission) {
                $normalized[] = $mappedPermission;

                foreach (self::impliedPermissions($mappedPermission) as $impliedPermission) {
                    $normalized[] = $impliedPermission;
                }
            }
        }

        if ($normalized !== []) {
            $normalized[] = 'dashboard.view';
        }

        $definitions = self::definitions();

        return array_values(array_filter(array_unique($normalized), fn(string $permission) => array_key_exists($permission, $definitions)));
    }

    protected static function impliedPermissions(string $permission): array
    {
        if (!str_ends_with($permission, '.manage')) {
            return [];
        }

        $viewPermission = substr($permission, 0, -strlen('.manage')) . '.view';

        return array_key_exists($viewPermission, self::definitions()) ? [$viewPermission] : [];
    }

    protected static function legacyMap(): array
    {
        return [
            'catalog.products.view' => ['catalog.products.view'],
            'catalog.products.manage' => ['catalog.products.manage'],
            'catalog.categories.manage' => ['catalog.categories.manage'],
            'catalog.variations.manage' => ['catalog.variations.manage'],

            'sales.orders.view' => ['sales.orders.view', 'sales.orders.report.view', 'shipping.delivery.view'],
            'sales.orders.manage' => ['sales.orders.manage', 'sales.orders.report.view', 'shipping.delivery.manage'],

            'shipping.settings.manage' => ['shipping.settings.view', 'shipping.settings.manage'],
            'store.flash-sales.manage' => ['store.flash-sales.manage'],
            'store.sync.manage' => ['store.sync.manage'],

            'branches.manage' => ['branches.manage'],

            'billing.payment-logs.view' => ['billing.payment-logs.view'],
            'billing.wallets.view' => ['billing.wallets.view', 'billing.transactions.view'],
            'billing.invoices.view' => ['billing.invoices.view'],

            'plans.packages.manage' => ['plans.packages.manage'],
            'plans.tenants.manage' => ['plans.tenants.manage'],

            'content.blog.manage' => ['content.blog-categories.manage', 'content.blog-posts.manage'],
            'content.pages.manage' => ['content.pages.manage'],
            'content.faqs.manage' => ['content.faqs.manage'],
            'content.newsletter.manage' => ['content.newsletter.manage'],
            'domains.requests.review' => ['domains.requests.manage'],

            'settings.general.manage' => ['settings.general.manage'],
            'settings.theme-colors.manage' => ['settings.theme-colors.manage'],
            'settings.home-variants.manage' => ['settings.home-variants.manage'],
            'settings.templates.manage' => ['settings.templates.manage'],
            'settings.email.manage' => ['settings.email-templates.manage', 'settings.email-configuration.manage'],
            'settings.currencies.manage' => ['settings.currencies.manage'],
            'settings.languages.manage' => ['settings.languages.manage'],
            'settings.countries.manage' => ['settings.countries.manage'],
            'settings.maintenance.manage' => ['settings.maintenance.manage'],

            'admins.users.manage' => ['admins.users.manage'],
            'admins.roles.manage' => ['admins.roles.manage'],

            'system.cache.manage' => ['system.cache.manage'],
        ];
    }
}
