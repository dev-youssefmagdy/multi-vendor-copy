<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Requests;

use App\Http\Requests\Tenant\Panel\TenantFormRequest;

final class StoreBrandRequestRequest extends TenantFormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:20', 'max:8000'],
            'files' => ['array', 'max:5'],
            'files.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,webp,gif,pdf,doc,docx,xls,xlsx,zip'],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'brand title',
            'description' => 'description',
            'files' => 'attachments',
            'files.*' => 'attachment',
        ];
    }
}
