<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdminRole extends Model
{
    use HasFactory;

    public const STORE_OWNER = 'Store Owner';
    public const MANAGER = 'Manager';
    public const SUPPORT = 'Support';

    protected $table = 'admin_roles';

    protected $fillable = [
        'name',
        'permissions',
        'permissions_count',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'permissions_count' => 'integer',
        ];
    }

    public function admins(): HasMany
    {
        return $this->hasMany(AdminUser::class, 'role_id');
    }

    public static function availablePermissions(): array
    {
        return [
            'dashboard.view' => 'View dashboard',
            'catalog.products.manage' => 'Manage products',
            'catalog.categories.manage' => 'Manage categories',
            'catalog.badges.manage' => 'Manage product badges',
            'sales.orders.view' => 'View orders',
            'sales.returns.manage' => 'Manage return requests',
            'sales.customers.manage' => 'Manage customers',
            'analytics.view' => 'View analytics',
            'finance.wallet.view' => 'View wallet',
            'finance.billing.view' => 'View billing',
            'finance.vendor-purchases.view' => 'View vendor purchases (buy from central)',
            'store.themes.manage' => 'Manage themes',
            'store.page-builder.manage' => 'Manage page builder',
            'store.home-variants.manage' => 'Manage home page variants',
            'store.pages.manage' => 'Manage pages',
            'store.coupons.manage' => 'Manage coupons',
            'store.flash-sales.manage' => 'Manage flash sales',
            'store.appearance.manage' => 'Manage appearance',
            'store.blade-theme.manage' => 'Manage vendor Blade storefront theme',
            'store.subscribers.manage' => 'Manage subscribers',
            'settings.tracking.manage' => 'Manage tracking pixels',
            'settings.regional.manage' => 'Manage currencies and languages',
            'settings.payment-gateways.manage' => 'Manage payment gateways',
            'settings.mail.manage' => 'Manage mail configurations',
            'settings.domains.manage' => 'Manage store domains',
            'settings.account.manage' => 'Manage account settings',
            'settings.admins.manage' => 'Manage tenant admins',
            'settings.roles.manage' => 'Manage tenant roles',
            'settings.languages.purchase' => 'Purchase paid languages',
            'settings.translations.manage' => 'Manage store translations',
        ];
    }

    public static function defaultRoleDefinitions(): array
    {
        $allPermissions = array_keys(self::availablePermissions());

        return [
            self::STORE_OWNER => $allPermissions,
            self::MANAGER => [
                'dashboard.view',
                'catalog.products.manage',
                'catalog.categories.manage',
                'catalog.badges.manage',
                'sales.orders.view',
                'sales.returns.manage',
                'sales.customers.manage',
                'analytics.view',
                'finance.wallet.view',
                'finance.billing.view',
                'finance.vendor-purchases.view',
                'store.page-builder.manage',
                'store.home-variants.manage',
                'store.pages.manage',
                'store.coupons.manage',
                'store.flash-sales.manage',
                'store.appearance.manage',
                'store.blade-theme.manage',
                'store.subscribers.manage',
                'settings.account.manage',
                'settings.tracking.manage',
            ],
            self::SUPPORT => [
                'dashboard.view',
                'sales.orders.view',
                'sales.returns.manage',
                'sales.customers.manage',
                'store.subscribers.manage',
                'settings.account.manage',
            ],
        ];
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? [], true);
    }
}
