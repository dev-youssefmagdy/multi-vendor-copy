<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Finance;

use App\Http\Requests\Tenant\Panel\Finance\Concerns\InlinePaymentRules;
use App\Http\Requests\Tenant\Panel\TenantFormRequest;
use App\Repositories\Tenant\TenantPanelRepository;

class SettleOrderRequest extends TenantFormRequest
{
    use InlinePaymentRules;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $gatewayCodes = collect(app(TenantPanelRepository::class)->centralGatewaysForPayment())
            ->pluck('code')
            ->all();

        return $this->inlinePaymentRules($gatewayCodes);
    }

    public function attributes(): array
    {
        return [
            'gateway' => 'payment gateway',
        ];
    }
}
