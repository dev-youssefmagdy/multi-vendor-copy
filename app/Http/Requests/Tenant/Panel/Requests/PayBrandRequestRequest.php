<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Requests;

use App\Http\Requests\Tenant\Panel\Finance\Concerns\InlinePaymentRules;
use App\Http\Requests\Tenant\Panel\TenantFormRequest;
use App\Models\BrandPaymentRequest;
use App\PaymentGateway\PaymentManager;
use Illuminate\Validation\Validator;

final class PayBrandRequestRequest extends TenantFormRequest
{
    use InlinePaymentRules;

    public function rules(): array
    {
        $gatewayCodes = app(PaymentManager::class)->vendorPaymentGateways()->pluck('code')->all();

        return array_merge($this->inlinePaymentRules($gatewayCodes), [
            'payment_request_id' => ['required', 'integer'],
        ]);
    }

    public function attributes(): array
    {
        return [
            'payment_request_id' => 'payment request',
            'gateway' => 'payment gateway',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $paymentRequestId = $this->input('payment_request_id');

            if (!$paymentRequestId) {
                return;
            }

            $exists = BrandPaymentRequest::where('id', $paymentRequestId)
                ->where('tenant_id', tenant('id'))
                ->where('brand_request_id', $this->route('id'))
                ->exists();

            if (!$exists) {
                $validator->errors()->add('payment_request_id', 'This payment request could not be found.');
            }
        });
    }
}
