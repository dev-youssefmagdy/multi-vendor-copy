<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Requests;

use App\Http\Requests\Tenant\Panel\TenantFormRequest;

final class ReplyProductRequestRequest extends TenantFormRequest
{
    public function rules(): array
    {
        return [
            'reply' => ['required', 'string', 'min:2', 'max:5000'],
            'attachments' => ['array', 'max:5'],
            'attachments.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,webp,gif,pdf,doc,docx'],
        ];
    }

    public function attributes(): array
    {
        return [
            'attachments.*' => 'attachment',
        ];
    }
}
