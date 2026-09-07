<?php

namespace App\Services;

use App\Enums\ActivationStatus;
use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Jobs\SyncTenantOwnerAdminJob;
use App\Jobs\SyncTenantSectionsJob;
use App\Services\Tenant\TenantCatalogSyncService;
use App\Services\Tenant\TenantPanelService;
use App\Services\StaticPageService;
use App\Models\Tenant\AdminRole as TenantAdminRole;
use App\Models\Tenant\AdminUser as TenantAdminUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Stancl\Tenancy\Database\Models\Domain;
use Stancl\Tenancy\Events\TenantCreated;

class TenantService
{
    public function __construct(
        protected TenantCatalogSyncService $tenantCatalogSyncService,
        protected TenantPanelService $tenantPanelService,
        protected StaticPageService $staticPageService,
    ) {
    }

    public function save(array $attributes, ?Tenant $tenant = null): Tenant
    {
        $previousEmail = $tenant?->email;
        $isNew = $tenant === null || !$tenant->exists;

        $tenant = DB::transaction(function () use ($attributes, $tenant) {
            $tenant ??= new Tenant(['id' => (string) Str::uuid()]);
            $slug = Str::slug($attributes['slug'] ?: $attributes['name']);

            $tenant->fill([
                'name' => $attributes['name'],
                'slug' => $slug,
                'email' => $attributes['email'],
                'phone' => $attributes['phone'] ?? null,
                'status' => $attributes['status'] ?? TenantStatus::Onboarding->value,
                'category_ids' => $attributes['category_ids'] ?? null,
                'primary_language_id' => $attributes['primary_language_id'] ?? null,
                'package_id' => $attributes['package_id'] ?? null,
                'trial_ends_at' => $attributes['trial_ends_at'] ?? null,
                'activated_at' => $attributes['status'] === TenantStatus::Active->value
                    ? ($tenant->activated_at ?? now())
                    : null,
                'profit_percentage' => max(0, (float) ($attributes['profit_percentage'] ?? 0)),
                'data' => array_merge($tenant->data ?? [], [
                    'shop_name' => $attributes['shop_name'] ?? null,
                ]),
            ]);

            // saveQuietly suppresses Eloquent-dispatched events (including TenantCreated),
            // preventing the synchronous CreateDatabase/MigrateDatabase job pipeline from
            // switching the PDO connection mid-transaction and invalidating it.
            $tenant->saveQuietly();

            // ── Subdomain entry ({slug}.nogrgr.com) ─────────────────────────
            // Always maintain a domain record for the tenant's subdomain so that
            // InitializeTenancyByDomainOrSubdomain can resolve it via the domains table.
            $centralDomain = collect(config('tenancy.central_domains', []))
                ->first(fn($d) => !in_array($d, ['127.0.0.1', 'localhost']));

            if ($centralDomain && filled($tenant->slug)) {
                $subdomainHost = $tenant->slug . '.' . $centralDomain;
                Domain::query()->firstOrCreate(
                    ['domain' => $subdomainHost],
                    ['tenant_id' => $tenant->id]
                );
            }

            // ── Custom domain (optional, provided by operator) ───────────────
            if (filled($attributes['domain'] ?? null)) {
                $customDomain = trim((string) $attributes['domain']);
                // Skip if it is already the auto-generated subdomain entry.
                if (!$centralDomain || $customDomain !== ($tenant->slug . '.' . $centralDomain)) {
                    Domain::query()->updateOrCreate(
                        ['domain' => $customDomain],
                        ['tenant_id' => $tenant->id]
                    );
                }
            }

            return $tenant->fresh(['package.translations.language', 'primaryLanguage', 'domains']);
        });

        // Fire TenantCreated after the central DB transaction commits so the job pipeline
        // (CreateDatabase, MigrateDatabase) runs without an active central transaction.
        if ($isNew) {
            event(new TenantCreated($tenant));

            // Ensure the central default footer pages exist before the "sync everything"
            // pass below pulls the `pages` section into the new tenant's database.
            $this->staticPageService->seedDefaultFooterPages();
        }

        SyncTenantOwnerAdminJob::dispatch($tenant->id, $attributes, $previousEmail);

        // On first registration sync everything; on updates exclude coupons/flash-sales
        // because those are already pushed to tenants whenever a central coupon/flash-sale
        // is created or updated (via their observers).
        $syncSections = $isNew
            ? null
            : array_values(array_diff($this->tenantCatalogSyncService->sections(), ['coupons', 'flash-sales']));

        SyncTenantSectionsJob::dispatch($tenant->id, $syncSections);

        return $tenant;
    }

    public function syncTenantOwnerAdmin(Tenant $tenant, array $attributes, ?string $previousEmail = null): void
    {
        tenancy()->initialize($tenant);

        //        try {
        $roles = [];

        foreach (TenantAdminRole::defaultRoleDefinitions() as $roleName => $permissions) {
            $roles[$roleName] = TenantAdminRole::query()->updateOrCreate(
                ['name' => $roleName],
                ['permissions' => $permissions, 'permissions_count' => count($permissions)]
            );
        }

        $email = strtolower(trim((string) ($attributes['email'] ?? $tenant->email)));
        $admin = TenantAdminUser::query()
            ->whereIn('email', array_values(array_filter([$email, $previousEmail])))
            ->first() ?? TenantAdminUser::query()->first();

        $admin ??= new TenantAdminUser();
        $admin->fill([
            'role_id' => $roles[TenantAdminRole::STORE_OWNER]->id,
            'name' => $attributes['name'] ?? ($tenant->data['shop_name'] ?? $tenant->name),
            'email' => $email,
            'status' => ActivationStatus::Active->value,
        ]);

        if (filled($attributes['password'] ?? null)) {
            $admin->password = Hash::make((string) $attributes['password']);
        }

        $admin->save();

        $shopName = (string) ($attributes['shop_name'] ?? $tenant->shop_name ?? $tenant->name ?? '');
        $this->tenantPanelService->seedAppearanceDefaults($shopName);
        //        } finally {
        tenancy()->end();
        //        }
    }
}
