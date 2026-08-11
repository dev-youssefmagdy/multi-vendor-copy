<?php

namespace App\Livewire\TenantOwner;

use App\Models\DnsRecord;
use App\Models\DomainRequest;
use App\Models\TenantOwner;
use App\Services\DnsRecordService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Stancl\Tenancy\Database\Models\Domain;

class DomainsList extends Component
{
    public bool $showAddModal = false;
    public string $newDomain = '';
    public ?string $dnsCheckingDomain = null;
    public array $dnsCheckResult = [];

    public bool $showEditModal = false;
    public ?int $editingRequestId = null;
    public string $editDomain = '';

    public function getOwner(): TenantOwner
    {
        /** @var TenantOwner $owner */
        $owner = Auth::guard('tenant_owner')->user();
        return $owner;
    }

    public function openAddModal(): void
    {
        $this->newDomain = '';
        $this->resetErrorBag();
        $this->showAddModal = true;
    }

    public function addDomain(): void
    {
        $this->validate([
            'newDomain' => [
                'required',
                'string',
                'max:255',
                'regex:/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/i',
                function ($attribute, $value, $fail) {
                    $normalized = strtolower(trim($value));
                    if (Domain::query()->where('domain', $normalized)->exists()) {
                        $fail(__('This domain is already registered.'));
                    }
                    if (DomainRequest::query()->where('domain', $normalized)->whereIn('status', ['pending', 'connected'])->exists()) {
                        $fail(__('A request for this domain is already pending or connected.'));
                    }
                },
            ],
        ]);

        $tenant = $this->getOwner()->selectedTenant;
        $normalized = strtolower(trim($this->newDomain));

        DomainRequest::create([
            'tenant_id' => $tenant->id,
            'domain' => $normalized,
            'status' => \App\Enums\DomainRequestStatus::Pending->value,
            'requested_at' => now(),
        ]);

        $this->showAddModal = false;
        $this->newDomain = '';
        session()->flash('success', __('Domain request submitted. Our team will review it.'));
    }

    public function openEditModal(int $domainRequestId): void
    {
        $tenant  = $this->getOwner()->selectedTenant;
        $request = DomainRequest::query()
            ->where('id', $domainRequestId)
            ->where('tenant_id', $tenant->id)
            ->firstOrFail();

        $this->editingRequestId = $domainRequestId;
        $this->editDomain       = $request->domain;
        $this->resetErrorBag();
        $this->showEditModal = true;
    }

    public function updateDomain(): void
    {
        $this->validate([
            'editDomain' => [
                'required',
                'string',
                'max:255',
                'regex:/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/i',
                function ($attribute, $value, $fail) {
                    $normalized = strtolower(trim($value));
                    $existing   = DomainRequest::query()
                        ->where('domain', $normalized)
                        ->where('id', '!=', $this->editingRequestId)
                        ->whereIn('status', ['pending', 'connected'])
                        ->exists();
                    if ($existing) {
                        $fail(__('This domain is already registered.'));
                    }
                    if (Domain::query()->where('domain', $normalized)->exists()) {
                        $fail(__('This domain is already in use.'));
                    }
                },
            ],
        ]);

        $tenant  = $this->getOwner()->selectedTenant;
        $request = DomainRequest::query()
            ->where('id', $this->editingRequestId)
            ->where('tenant_id', $tenant->id)
            ->firstOrFail();

        $normalized = strtolower(trim($this->editDomain));

        // If the domain was previously connected, remove the old domain entry
        if ($request->status === \App\Enums\DomainRequestStatus::Connected) {
            Domain::query()
                ->where('domain', $request->domain)
                ->where('tenant_id', $tenant->id)
                ->delete();
        }

        $request->update([
            'domain'       => $normalized,
            'status'       => \App\Enums\DomainRequestStatus::Pending->value,
            'requested_at' => now(),
            'verified_at'  => null,
        ]);

        // Clear DNS result if it was for the old domain
        if (($this->dnsCheckResult['domain'] ?? null) === $request->getOriginal('domain')) {
            $this->dnsCheckResult = [];
        }

        $this->showEditModal    = false;
        $this->editingRequestId = null;
        $this->editDomain       = '';

        session()->flash('success', __('Domain updated. Please re-verify the DNS records.'));
    }

    public function removeDomain(int $domainRequestId): void
    {
        $tenant = $this->getOwner()->selectedTenant;

        $request = DomainRequest::query()
            ->where('id', $domainRequestId)
            ->where('tenant_id', $tenant->id)
            ->firstOrFail();

        // Also remove the actual domain entry if it was connected
        Domain::query()
            ->where('domain', $request->domain)
            ->where('tenant_id', $tenant->id)
            ->delete();

        $request->delete();

        session()->flash('success', __('Domain removed.'));
    }

    public function checkDns(string $domain): void
    {
        // Toggle off if already showing this domain's result
        if (($this->dnsCheckResult['domain'] ?? null) === $domain) {
            $this->dnsCheckResult = [];
            $this->dnsCheckingDomain = null;
            return;
        }

        $this->dnsCheckingDomain = $domain;
        $this->dnsCheckResult = [];

        $records = DnsRecord::all();
        $service = app(DnsRecordService::class);
        $result = $service->checkDomain($domain, $records);

        $this->dnsCheckResult = [
            'domain' => $domain,
            'connected' => $result['connected'],
            'checks' => collect($result['checks'])->map(fn($c) => [
                'type' => $c['record']->type,
                'name' => $c['record']->name,
                'value' => $c['record']->value,
                'ok' => $c['ok'],
            ])->all(),
        ];
    }

    public function secretAdminLogin(string $tenantId): void
    {
        $owner = $this->getOwner();

        if ($owner->selected_tenant_id !== $tenantId) {
            abort(403);
        }

        $tenant = $owner->selectedTenant()->with('domains')->first();

        tenancy()->initialize($tenant);
        $admin = \App\Models\Tenant\AdminUser::query()->first();
        tenancy()->end();

        if (!$admin) {
            session()->flash('error', __('No admin user found in this store.'));
            return;
        }

        $token = Str::random(64);

        Cache::put(
            "tenant_impersonate:{$token}",
            [
                'tenant_id' => $tenant->id,
                'admin_id' => $admin->id,
                'admin_name' => $owner->email,
            ],
            now()->addSeconds(90),
        );

        $centralDomain = collect(config('tenancy.central_domains', []))
            ->first(fn($d) => !in_array($d, ['127.0.0.1', 'localhost']));

        $host = ($centralDomain && filled($tenant->slug))
            ? $tenant->slug . '.' . $centralDomain
            : $tenant->domains->first()?->domain;

        if (!$host) {
            session()->flash('error', __('Could not resolve store domain.'));
            return;
        }

        $scheme = request()->isSecure() ? 'https' : 'http';
        $this->redirect("{$scheme}://{$host}/admin/impersonate/{$token}", navigate: false);
    }

    #[Layout('layouts.owner')]
    public function render(): \Illuminate\View\View
    {
        $tenant = $this->getOwner()->selectedTenant()->with('domains')->first();

        $domainRequests = DomainRequest::query()
            ->where('tenant_id', $tenant->id)
            ->orderByDesc('requested_at')
            ->get();

        return view('livewire.owner.domains-list', [
            'tenant' => $tenant,
            'domains' => $tenant->domains,
            'domainRequests' => $domainRequests,
        ]);
    }
}
