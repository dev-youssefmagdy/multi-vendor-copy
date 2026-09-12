<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Sales;

use App\Http\Requests\Tenant\Panel\TenantFormRequest;

final class RequestReturnInfoRequest extends TenantFormRequest
{
    public function rules(): array
    {
        return [
            'info_message' => ['required', 'string', 'max:2000'],
        ];
    }
}
