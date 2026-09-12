<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Requests;

use App\Http\Requests\Tenant\Panel\TenantFormRequest;

final class SendBrandRequestMessageRequest extends TenantFormRequest
{
    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'message' => 'message',
        ];
    }
}
