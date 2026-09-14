<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Support;

use App\Http\Requests\Tenant\Panel\TenantFormRequest;

final class ReplyTicketRequest extends TenantFormRequest
{
    public function rules(): array
    {
        return [
            'reply' => ['required', 'string', 'min:2', 'max:5000'],
        ];
    }
}
