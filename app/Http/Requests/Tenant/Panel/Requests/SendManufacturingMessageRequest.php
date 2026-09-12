<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Requests;

use App\Http\Requests\Tenant\Panel\TenantFormRequest;

final class SendManufacturingMessageRequest extends TenantFormRequest
{
    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:2000'],
        ];
    }
}
