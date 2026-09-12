<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Shell;

use App\Http\Requests\Tenant\Panel\TenantFormRequest;

final class AcceptComplianceRequest extends TenantFormRequest
{
    public function rules(): array
    {
        return [
            'accept' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'accept.accepted' => 'Please confirm you have read and accept the compliance documents.',
        ];
    }
}
