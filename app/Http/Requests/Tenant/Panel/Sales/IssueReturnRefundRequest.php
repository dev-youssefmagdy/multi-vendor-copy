<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Sales;

use App\Http\Requests\Tenant\Panel\TenantFormRequest;
use App\Models\ReturnRequest;
use App\Services\Refunds\RefundService;
use Illuminate\Validation\Validator;
use Throwable;

/**
 * Tenant panel: refund an inspected return. The amount may be lowered but never raised above the
 * calculated maximum (RefundCalculator); RefundService enforces the same cap on submit.
 */
final class IssueReturnRefundRequest extends TenantFormRequest
{
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.min' => __('The refund amount must be greater than zero.'),
        ];
    }

    /** @return list<callable> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('amount')) {
                    return;
                }

                $record = ReturnRequest::query()->find((int) $this->route('id'));

                if (! $record || (string) $record->tenant_id !== (string) tenant('id')) {
                    return; // the controller answers 404 / 403
                }

                try {
                    $max = app(RefundService::class)->calculateForReturn($record)->max;
                } catch (Throwable) {
                    return; // the service reports the real problem on submit
                }

                if (round((float) $this->input('amount'), 2) > round($max, 2)) {
                    $validator->errors()->add('amount', $max > 0
                        ? __('The refund cannot be higher than the calculated maximum of :amount.', ['amount' => number_format($max, 2)])
                        : __('There is nothing left to refund on this order.'));
                }
            },
        ];
    }
}
