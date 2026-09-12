<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Catalog;

use App\Http\Requests\Tenant\Panel\TenantFormRequest;

final class FetchAiPriceRequest extends TenantFormRequest
{
    protected function booleanFields(): array
    {
        return ['use_image'];
    }

    public function rules(): array
    {
        return [
            'use_image' => ['boolean'],
            'variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
        ];
    }
}
