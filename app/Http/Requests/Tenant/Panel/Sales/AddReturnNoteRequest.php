<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Sales;

use App\Http\Requests\Tenant\Panel\TenantFormRequest;

final class AddReturnNoteRequest extends TenantFormRequest
{
    public function rules(): array
    {
        return [
            'note_text' => ['required', 'string', 'max:2000'],
        ];
    }
}
