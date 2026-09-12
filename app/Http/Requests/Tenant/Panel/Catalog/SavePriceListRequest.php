<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Catalog;

use App\Http\Requests\Tenant\Panel\TenantFormRequest;
use Illuminate\Validation\Rule;

final class SavePriceListRequest extends TenantFormRequest
{
    public function rules(): array
    {
        return [
            'profits' => ['required', 'array'],
            'profits.*.type' => ['required', 'string', Rule::in(['percentage', 'fixed'])],
            'profits.*.value' => ['required', 'numeric', 'min:0'],
            'variants' => ['nullable', 'array'],
            'variants.*.id' => ['required', 'integer', 'exists:product_variants,id'],
            'variants.*.real_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.profits' => ['required', 'array'],
            'variants.*.profits.*.type' => ['required', 'string', Rule::in(['percentage', 'fixed'])],
            'variants.*.profits.*.value' => ['required', 'numeric', 'min:0'],
        ];
    }
}
