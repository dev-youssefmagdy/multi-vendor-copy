<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Settings;

use App\Enums\DomainRequestStatus;
use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Http\Requests\Tenant\Panel\Settings\AddDomainRequest;
use App\Http\Requests\Tenant\Panel\Settings\UpdateDomainRequest;
use App\Models\DnsRecord;
use App\Models\DomainRequest;
use App\Services\DnsRecordService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Stancl\Tenancy\Database\Models\Domain;

final class DomainsController extends PanelController
{
    public function __construct(private readonly DnsRecordService $dnsRecordService)
    {
    }

    public function index(): View
    {
        $tenant = tenant();

        $domainRequests = DomainRequest::query()
            ->where('tenant_id', $tenant->id)
            ->orderByDesc('requested_at')
            ->get();

        return view('tenant.pages.settings.domains.index', [
            'tenant' => $tenant,
            'domains' => Domain::query()->where('tenant_id', $tenant->id)->get(),
            'domainRequests' => $domainRequests,
        ]);
    }

    public function store(AddDomainRequest $request): JsonResponse
    {
        $validated = $request->validated();

        DomainRequest::create([
            'tenant_id' => tenant()->id,
            'domain' => $validated['domain'],
            'status' => DomainRequestStatus::Pending->value,
            'requested_at' => now(),
        ]);

        return $this->success(__('Domain request submitted. Our team will review it.'));
    }

    public function validateStore(AddDomainRequest $request): JsonResponse
    {
        return $this->validFormResponse();
    }

    public function show(DomainRequest $domainRequest): JsonResponse
    {
        $this->authorizeTenantOwnership($domainRequest);

        return response()->json(['data' => [
            'domain' => $domainRequest->domain,
        ]]);
    }

    public function update(UpdateDomainRequest $request, DomainRequest $domainRequest): JsonResponse
    {
        $this->authorizeTenantOwnership($domainRequest);

        $tenant = tenant();
        $validated = $request->validated();

        if ($domainRequest->status === DomainRequestStatus::Connected) {
            Domain::query()
                ->where('domain', $domainRequest->domain)
                ->where('tenant_id', $tenant->id)
                ->delete();
        }

        $domainRequest->update([
            'domain' => $validated['domain'],
            'status' => DomainRequestStatus::Pending->value,
            'requested_at' => now(),
            'verified_at' => null,
        ]);

        return $this->success(__('Domain updated. Please re-verify the DNS records.'));
    }

    public function validateUpdate(UpdateDomainRequest $request, DomainRequest $domainRequest): JsonResponse
    {
        return $this->validFormResponse();
    }

    public function destroy(DomainRequest $domainRequest): JsonResponse
    {
        $this->authorizeTenantOwnership($domainRequest);

        $tenant = tenant();

        Domain::query()
            ->where('domain', $domainRequest->domain)
            ->where('tenant_id', $tenant->id)
            ->delete();

        $domainRequest->delete();

        return $this->success(__('Domain removed.'));
    }

    public function checkDns(Request $request, DomainRequest $domainRequest): JsonResponse
    {
        $this->authorizeTenantOwnership($domainRequest);

        $records = DnsRecord::all();
        $result = $this->dnsRecordService->checkDomain($domainRequest->domain, $records);

        $data = [
            'domain' => $domainRequest->domain,
            'connected' => $result['connected'],
            'checks' => collect($result['checks'])->map(fn (array $c) => [
                'type' => $c['record']->type,
                'name' => $c['record']->name,
                'value' => $c['record']->value,
                'ok' => $c['ok'],
            ])->all(),
        ];

        $message = $result['connected']
            ? __('Domain is fully connected.')
            : __('DNS not fully configured yet.');

        return response()->json([
            'success' => true,
            'message' => $message,
            'toast_type' => $result['connected'] ? 'success' : 'warning',
            'data' => $data,
        ]);
    }

    private function authorizeTenantOwnership(DomainRequest $domainRequest): void
    {
        abort_unless($domainRequest->tenant_id === tenant()->id, 404);
    }
}
