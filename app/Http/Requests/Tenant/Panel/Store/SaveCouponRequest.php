<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Store;

use App\Http\Requests\Tenant\Panel\TenantFormRequest;
use App\Repositories\Tenant\Catalog\CouponCatalog;
use Illuminate\Validation\Rule;

final class SaveCouponRequest extends TenantFormRequest
{
    use ScopesToCountries;

    public function rules(): array
    {
        $couponId = $this->route('coupon')?->id;
        $current = $this->route('coupon')?->code;

        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('coupons', 'code')->ignore($couponId),
                // Dedup plan D6 (central mode): a code a live central coupon uses cannot be taken by an own coupon. In
                // sync mode the central copy row already blocks it through the unique rule above.
                function (string $attribute, mixed $value, \Closure $fail) use ($current) {
                    // D-P3-2: an update that keeps the coupon's own code is not a new claim on it.
                    if ($current !== null && is_string($value) && mb_strtolower(trim($value)) === mb_strtolower(trim($current))) {
                        return;
                    }
                    if (CouponCatalog::enabled() && is_string($value) && app(CouponCatalog::class)->centralCodeExists($value)) {
                        $fail(__('This code is already used by a platform coupon. Choose another code.'));
                    }
                },
            ],
            'name_text' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:fixed,percentage'],
            'value' => ['required', 'numeric', 'min:0'],
            'minimum_spend' => ['nullable', 'numeric', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }

    public function attributes(): array
    {
        return [
            'country_ids' => __('countries'),
            'name_text' => __('name'),
            'minimum_spend' => __('minimum spend'),
            'start_date' => __('start date'),
            'end_date' => __('end date'),
        ];
    }
}
