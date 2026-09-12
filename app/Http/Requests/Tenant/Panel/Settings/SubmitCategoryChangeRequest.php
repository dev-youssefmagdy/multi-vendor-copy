<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Settings;

use App\Http\Requests\Tenant\Panel\TenantFormRequest;

final class SubmitCategoryChangeRequest extends TenantFormRequest
{
    public function rules(): array
    {
        return [
            'requested_category_ids' => ['required', 'array', 'min:1'],
            'requested_category_ids.*' => ['integer'],
        ];
    }

    public function attributes(): array
    {
        return [
            'requested_category_ids' => 'categories',
        ];
    }
}
