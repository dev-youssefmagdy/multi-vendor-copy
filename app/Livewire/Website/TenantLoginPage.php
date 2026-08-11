<?php

declare(strict_types=1);

namespace App\Livewire\Website;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Central website login page for tenant admins.
 *
 * Accepts an email + password, searches every active tenant's database for a
 * matching admin user, then uses the same impersonation-token mechanism to
 * auto-log the user into their tenant admin panel.
 *
 * If credentials match more than one tenant the user is shown a store picker.
 */
#[Layout('layouts.website')]
class TenantLoginPage extends Component
{
    public string $email = '';
    public string $password = '';
    public bool $remember = false;

    /** @var array<int,array{tenant_id:string,name:string,slug:string,domain:string}> */
    public array $matchedStores = [];

    public bool $showPicker = false;

    public function login(): void
    {
        $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $email = strtolower(trim($this->email));
        $password = $this->password;

        // ── Search all active tenants ─────────────────────────────────────
        /** @var Tenant[] $tenants */
        $tenants = Tenant::query()
            ->with('domains')
            ->get();

        $matched = [];

        foreach ($tenants as $tenant) {
            try {
                tenancy()->initialize($tenant);

                $admin = \App\Models\Tenant\AdminUser::query()
                    ->where('email', $email)
                    ->first();


                if ($admin && Hash::check($password, $admin->password)) {
                    $centralDomain = collect(config('tenancy.central_domains', []))
                        ->first(fn($d) => !in_array($d, ['127.0.0.1', 'localhost']));

                    $subdomainHost = $centralDomain && filled($tenant->slug)
                        ? $tenant->slug . '.' . $centralDomain
                        : null;

                    $host = $subdomainHost ?? $tenant->domains->first()?->domain;

                    if ($host) {
                        $matched[] = [
                            'tenant_id' => $tenant->id,
                            'name' => $tenant->data['shop_name'] ?? $tenant->name,
                            'slug' => $tenant->slug ?? '',
                            'domain' => $host,
                            'admin_id' => $admin->id,
                        ];
                    }
                }
            } catch (\Throwable) {
                // Tenant DB unreachable – skip silently.
            } finally {
                tenancy()->end();
            }
        }

        if (empty($matched)) {
            $this->addError('email', __('No store found with these credentials.'));
            return;
        }

        // Single match → redirect immediately.
        if (count($matched) === 1) {
            $this->redirectToStore($matched[0]);
            return;
        }

        // Multiple matches → show store picker.
        // Strip sensitive data before storing in the Livewire property.
        $this->matchedStores = array_map(fn($m) => [
            'tenant_id' => $m['tenant_id'],
            'name' => $m['name'],
            'slug' => $m['slug'],
            'domain' => $m['domain'],
        ], $matched);

        // Store the admin_id map in cache keyed by tenant_id so the pick action
        // can retrieve it without re-querying all tenants.
        $pickToken = Str::random(32);
        Cache::put(
            "tenant_login_pick:{$pickToken}",
            collect($matched)->keyBy('tenant_id')->map(fn($m) => $m['admin_id'])->all(),
            now()->addMinutes(5),
        );
        session(['tenant_login_pick_token' => $pickToken]);

        $this->showPicker = true;
    }

    public function pickStore(string $tenantId): void
    {
        $pickToken = session('tenant_login_pick_token');
        $map = $pickToken ? Cache::get("tenant_login_pick:{$pickToken}") : null;

        if (!$map || !isset($map[$tenantId])) {
            $this->addError('email', __('Session expired. Please log in again.'));
            $this->showPicker = false;
            return;
        }

        $store = collect($this->matchedStores)->firstWhere('tenant_id', $tenantId);
        if (!$store) {
            return;
        }

        Cache::forget("tenant_login_pick:{$pickToken}");
        session()->forget('tenant_login_pick_token');

        $this->redirectToStore(array_merge($store, ['admin_id' => $map[$tenantId]]));
    }

    private function redirectToStore(array $match): void
    {
        $token = Str::random(64);

        Cache::put(
            "tenant_impersonate:{$token}",
            [
                'tenant_id' => $match['tenant_id'],
                'admin_id' => $match['admin_id'],
                'admin_name' => $this->email,
            ],
            now()->addSeconds(90),
        );

        $scheme = request()->isSecure() ? 'https' : 'http';
        $targetUrl = "{$scheme}://{$match['domain']}/admin/impersonate/{$token}";

        $this->redirect($targetUrl, navigate: false);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.website.tenant-login');
    }
}
