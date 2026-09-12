<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Finance;

use App\Http\Requests\Tenant\Panel\Finance\Concerns\InlinePaymentRules;
use App\Http\Requests\Tenant\Panel\TenantFormRequest;
use App\PaymentGateway\PaymentManager;

final class BuyLanguageRequest extends TenantFormRequest
{
    use InlinePaymentRules;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $gatewayCodes = app(PaymentManager::class)->vendorPaymentGateways()->pluck('code')->all();

        return array_merge($this->inlinePaymentRules($gatewayCodes), [
            'language_id' => ['required', 'integer'],
        ]);
    }

    public function attributes(): array
    {
        return [
            'language_id' => 'language',
            'gateway' => 'payment gateway',
        ];
    }
}
