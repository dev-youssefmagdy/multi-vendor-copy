<?php

namespace App\Services;

use App\Enums\DomainRequestStatus;
use App\Models\DomainRequest;
use Illuminate\Support\Facades\DB;

class DomainRequestService
{
    public function save(array $attributes, ?DomainRequest $request = null): DomainRequest
    {
        return DB::transaction(function () use ($attributes, $request) {
            $request ??= new DomainRequest();
            $status = $attributes['status'];
            $verifiedAt = $attributes['verified_at'] ?? null;

            if ($status === DomainRequestStatus::Connected->value && blank($verifiedAt)) {
                $verifiedAt = now();
            }

            if ($status === DomainRequestStatus::Pending->value) {
                $verifiedAt = null;
            }

            $request->fill([
                'tenant_id' => $attributes['tenant_id'] ?: null,
                'domain' => strtolower(trim((string) $attributes['domain'])),
                'status' => $status,
                'requested_at' => $attributes['requested_at'] ?? now(),
                'verified_at' => $verifiedAt,
            ]);
            $request->save();

            return $request->fresh('tenant');
        });
    }

    public function markStatus(DomainRequest $request, DomainRequestStatus $status): DomainRequest
    {
        return $this->save([
            'tenant_id' => $request->tenant_id,
            'domain' => $request->domain,
            'status' => $status->value,
            'requested_at' => $request->requested_at,
            'verified_at' => $status === DomainRequestStatus::Connected ? now() : null,
        ], $request);
    }

    public function delete(DomainRequest $request): void
    {
        $request->delete();
    }
}
