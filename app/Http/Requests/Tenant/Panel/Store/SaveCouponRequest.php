<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Store;

use App\Http\Requests\Tenant\Panel\TenantFormRequest;
use Illuminate\Validation\Rule;

final class SaveCouponRequest extends TenantFormRequest
{
    public function rules(): array
    {
        $couponId = $this->route('coupon')?->id;

        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('coupons', 'code')->ignore($couponId)],
            'name_text' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:fixed,percentage'],
            'value' => ['required', 'numeric', 'min:0'],
            'minimum_spend' => ['nullable', 'numeric', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'country_id' => ['nullable', 'integer'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name_text' => 'name',
            'minimum_spend' => 'minimum spend',
            'start_date' => 'start date',
            'end_date' => 'end date',
        ];
    }
}
