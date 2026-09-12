<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Settings;

use App\Http\Requests\Tenant\Panel\TenantFormRequest;
use App\Models\DomainRequest;
use Stancl\Tenancy\Database\Models\Domain;

final class UpdateDomainRequest extends TenantFormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'domain' => strtolower(trim((string) $this->input('domain'))),
        ]);
    }

    public function rules(): array
    {
        $domainRequestId = $this->route('domainRequest')?->id;

        return [
            'domain' => [
                'required',
                'string',
                'max:255',
                'regex:/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/i',
                function ($attribute, $value, $fail) use ($domainRequestId): void {
                    $existing = DomainRequest::query()
                        ->where('domain', $value)
                        ->where('id', '!=', $domainRequestId)
                        ->whereIn('status', ['pending', 'connected'])
                        ->exists();
                    if ($existing) {
                        $fail(__('This domain is already registered.'));
                    }
                    if (Domain::query()->where('domain', $value)->exists()) {
                        $fail(__('This domain is already in use.'));
                    }
                },
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'domain' => 'domain',
        ];
    }
}
