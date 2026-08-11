<?php

namespace App\Livewire\Tenant\Setting;

use App\Livewire\Tenant\Base\ListPage;
use App\Livewire\Tenant\Concerns\InteractsWithTenantUi;
use App\Models\DnsRecord;
use App\Models\DomainRequest;
use App\Services\DnsRecordService;
use Stancl\Tenancy\Database\Models\Domain;

class DomainsList extends ListPage
{
    use InteractsWithTenantUi;

    public bool $showAddModal = false;
    public string $newDomain = '';
    public ?string $dnsCheckingDomain = null;
    public array $dnsCheckResult = [];

    public bool $showEditModal = false;
    public ?int $editingRequestId = null;
    public string $editDomain = '';

    protected function pageView(): string
    {
        return 'livewire.tenant.setting.domains-list';
    }

    protected function pageMeta(): array
    {
        return [
            'title' => 'Domains',
            'badge' => 'Store',
            'description' => 'Manage your store domains and DNS connection status.',
        ];
    }

    protected function pageData(): array
    {
        $tenant = tenant();

        $domainRequests = DomainRequest::query()
            ->where('tenant_id', $tenant->id)
            ->orderByDesc('requested_at')
            ->get();

        return array_merge(parent::pageData(), [
            'tenant' => $tenant,
            'domains' => Domain::query()->where('tenant_id', $tenant->id)->get(),
            'domainRequests' => $domainRequests,
        ]);
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

        $normalized = strtolower(trim($this->newDomain));

        DomainRequest::create([
            'tenant_id' => tenant()->id,
            'domain' => $normalized,
            'status' => \App\Enums\DomainRequestStatus::Pending->value,
            'requested_at' => now(),
        ]);

        $this->showAddModal = false;
        $this->newDomain = '';
        $this->toast(__('Domain request submitted. Our team will review it.'));
    }

    public function openEditModal(int $domainRequestId): void
    {
        $request = DomainRequest::query()
            ->where('id', $domainRequestId)
            ->where('tenant_id', tenant()->id)
            ->firstOrFail();

        $this->editingRequestId = $domainRequestId;
        $this->editDomain = $request->domain;
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
                    $existing = DomainRequest::query()
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

        $tenant = tenant();
        $request = DomainRequest::query()
            ->where('id', $this->editingRequestId)
            ->where('tenant_id', $tenant->id)
            ->firstOrFail();

        $normalized = strtolower(trim($this->editDomain));

        if ($request->status === \App\Enums\DomainRequestStatus::Connected) {
            Domain::query()
                ->where('domain', $request->domain)
                ->where('tenant_id', $tenant->id)
                ->delete();
        }

        $request->update([
            'domain' => $normalized,
            'status' => \App\Enums\DomainRequestStatus::Pending->value,
            'requested_at' => now(),
            'verified_at' => null,
        ]);

        if (($this->dnsCheckResult['domain'] ?? null) === $request->getOriginal('domain')) {
            $this->dnsCheckResult = [];
        }

        $this->showEditModal = false;
        $this->editingRequestId = null;
        $this->editDomain = '';

        $this->toast(__('Domain updated. Please re-verify the DNS records.'));
    }

    public function removeDomain(int $domainRequestId): void
    {
        $tenant = tenant();

        $request = DomainRequest::query()
            ->where('id', $domainRequestId)
            ->where('tenant_id', $tenant->id)
            ->firstOrFail();

        Domain::query()
            ->where('domain', $request->domain)
            ->where('tenant_id', $tenant->id)
            ->delete();

        $request->delete();

        $this->toast(__('Domain removed.'));
    }

    public function checkDns(string $domain): void
    {
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
            'checks' => collect($result['checks'])->map(fn ($c) => [
                'type' => $c['record']->type,
                'name' => $c['record']->name,
                'value' => $c['record']->value,
                'ok' => $c['ok'],
            ])->all(),
        ];
    }
}
