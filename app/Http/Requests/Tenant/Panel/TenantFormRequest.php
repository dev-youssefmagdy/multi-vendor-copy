<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel;

use Illuminate\Foundation\Http\FormRequest;

abstract class TenantFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function booleanFields(): array
    {
        return [];
    }

    protected function prepareForValidation(): void
    {
        $fields = $this->booleanFields();

        if ($fields === []) {
            return;
        }

        $this->merge(
            collect($fields)->mapWithKeys(fn (string $field) => [$field => $this->boolean($field)])->all(),
        );
    }
}
