<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Store;

use App\Http\Requests\Tenant\Panel\TenantFormRequest;

final class SaveFlashSaleRequest extends TenantFormRequest
{
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
            'country_id' => ['nullable', 'integer'],
        ];
    }

    public function attributes(): array
    {
        return [
            'product_ids' => 'products',
            'discount_percentage' => 'discount percentage',
            'start_date' => 'start date',
            'end_date' => 'end date',
            'banner_image' => 'banner image',
        ];
    }
}
