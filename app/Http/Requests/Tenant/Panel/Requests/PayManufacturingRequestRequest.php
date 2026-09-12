<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Requests;

use App\Enums\ManufacturingPaymentRequestStatus;
use App\Http\Requests\Tenant\Panel\TenantFormRequest;
use App\Http\Requests\Tenant\Panel\Finance\Concerns\InlinePaymentRules;
use App\Models\ManufacturingPaymentRequest;
use App\PaymentGateway\PaymentManager;
use Illuminate\Contracts\Validation\Validator;

final class PayManufacturingRequestRequest extends TenantFormRequest
{
    use InlinePaymentRules;

    public function rules(): array
    {
        $gatewayCodes = collect(app(PaymentManager::class)->vendorPaymentGateways())
            ->pluck('code')
            ->all();

        return array_merge($this->inlinePaymentRules($gatewayCodes), [
            'payment_request_id' => ['required', 'integer'],
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $paymentRequestId = $this->input('payment_request_id');

            if (!$paymentRequestId) {
                return;
            }

            $requestId = (int) $this->route('id');

            $owned = ManufacturingPaymentRequest::query()
                ->where('id', $paymentRequestId)
                ->where('manufacturing_request_id', $requestId)
                ->where('tenant_id', tenant('id'))
                ->where('status', ManufacturingPaymentRequestStatus::Pending->value)
                ->exists();

            if (!$owned) {
                $validator->errors()->add('payment_request_id', 'This payment request could not be found.');
            }
        });
    }

    public function attributes(): array
    {
        return [
            'gateway' => 'payment gateway',
        ];
    }
}
