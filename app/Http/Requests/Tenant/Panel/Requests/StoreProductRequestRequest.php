<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Requests;

use App\Http\Requests\Tenant\Panel\TenantFormRequest;

final class StoreProductRequestRequest extends TenantFormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:20', 'max:8000'],
            'product_url' => ['nullable', 'url', 'max:2000'],
            'files' => ['array', 'max:5'],
            'files.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,webp,gif,pdf,doc,docx,xls,xlsx,zip'],
        ];
    }

    public function attributes(): array
    {
        return [
            'product_url' => 'product URL',
            'files.*' => 'attachment',
        ];
    }
}
