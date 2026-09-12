<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Settings;

use App\Http\Requests\Tenant\Panel\Finance\Concerns\InlinePaymentRules;
use App\Http\Requests\Tenant\Panel\TenantFormRequest;
use App\PaymentGateway\PaymentManager;

final class PurchaseAiTranslationRequest extends TenantFormRequest
{
    use InlinePaymentRules;

    public function rules(): array
    {
        $gatewayCodes = app(PaymentManager::class)->vendorPaymentGateways()->pluck('code')->all();

        return $this->inlinePaymentRules($gatewayCodes);
    }

    public function attributes(): array
    {
        return [
            'gateway' => 'payment gateway',
        ];
    }
}
