<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Finance;

use App\Http\Requests\Tenant\Panel\TenantFormRequest;
use App\Support\AffiliateSettings;

final class PartnerWithdrawRequest extends TenantFormRequest
{
    public function rules(): array
    {
        // Minimum withdrawal amount is admin-configurable (Affiliates > Program Settings).
        return [
            'amount' => ['required', 'numeric', 'gt:0', 'min:' . AffiliateSettings::minPayout(), 'max:9999999'],
            'note'   => ['nullable', 'string', 'max:500'],
        ];
    }
}
