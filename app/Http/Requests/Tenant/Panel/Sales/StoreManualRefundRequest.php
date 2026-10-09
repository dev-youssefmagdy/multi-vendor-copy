<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Sales;

use App\Http\Requests\Tenant\Panel\TenantFormRequest;
use App\Models\Tenant\Order;
use App\Services\Refunds\RefundService;
use Illuminate\Validation\Validator;

/** Tenant panel: a manual refund on an order (amount ≤ what is still refundable, reason required). */
final class StoreManualRefundRequest extends TenantFormRequest
{
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999'],
            'reason' => ['required', 'string', 'max:255'],
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

                $order = Order::query()->with('items')->find((int) $this->route('orderId'));

                if (! $order) {
                    return; // the controller answers 404
                }

                $refundable = app(RefundService::class)->refundableAmount($order);

                if (round((float) $this->input('amount'), 2) > $refundable) {
                    $validator->errors()->add('amount', $refundable > 0
                        ? __('The refund exceeds the amount that can still be refunded on this order (:amount).', ['amount' => number_format($refundable, 2)])
                        : __('There is nothing left to refund on this order.'));
                }
            },
        ];
    }
}
