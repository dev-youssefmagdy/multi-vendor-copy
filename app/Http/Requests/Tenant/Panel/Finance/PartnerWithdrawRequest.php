<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Finance;

use App\Http\Requests\Tenant\Panel\TenantFormRequest;

final class PartnerWithdrawRequest extends TenantFormRequest
{
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999'],
            'note'   => ['nullable', 'string', 'max:500'],
        ];
    }
}
