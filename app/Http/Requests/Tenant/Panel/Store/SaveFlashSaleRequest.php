<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Store;

use App\Http\Requests\Tenant\Panel\TenantFormRequest;

final class SaveFlashSaleRequest extends TenantFormRequest
{
    use ScopesToCountries;

    protected function booleanFields(): array
    {
        return ['active'];
    }

    public function rules(): array
    {
        return [
            'product_ids' => ['required', 'array', 'min:1'],
            'product_ids.*' => ['integer', 'exists:products,id'],
            'discount_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'active' => ['boolean'],
            'banner_image' => ['nullable', 'image'],
        ];
    }

    public function attributes(): array
    {
        return [
            'country_ids' => __('countries'),
            'product_ids' => __('products'),
            'discount_percentage' => __('discount percentage'),
            'start_date' => __('start date'),
            'end_date' => __('end date'),
            'banner_image' => __('banner image'),
        ];
    }
}
