<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Settings;

use App\Http\Requests\Tenant\Panel\TenantFormRequest;
use App\Models\DomainRequest;
use Stancl\Tenancy\Database\Models\Domain;

final class AddDomainRequest extends TenantFormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'domain' => strtolower(trim((string) $this->input('domain'))),
        ]);
    }

    public function rules(): array
    {
        return [
            'domain' => [
                'required',
                'string',
                'max:255',
                'regex:/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/i',
                function ($attribute, $value, $fail): void {
                    if (Domain::query()->where('domain', $value)->exists()) {
                        $fail(__('This domain is already registered.'));
                    }
                    if (DomainRequest::query()->where('domain', $value)->whereIn('status', ['pending', 'connected'])->exists()) {
                        $fail(__('A request for this domain is already pending or connected.'));
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
