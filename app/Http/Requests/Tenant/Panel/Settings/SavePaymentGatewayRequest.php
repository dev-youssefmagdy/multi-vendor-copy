<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Settings;

use App\Http\Requests\Tenant\Panel\TenantFormRequest;

final class SavePaymentGatewayRequest extends TenantFormRequest
{
    public function rules(): array
    {
        $rules = [
            'sandbox_mode' => ['boolean'],
            'use_own' => ['boolean'],
            'required_fields' => ['array'],
            'required_fields.*.key' => ['required', 'string'],
        ];

        $useOwn = $this->boolean('use_own');
        $fields = (array) $this->input('required_fields', []);

        foreach ($fields as $index => $field) {
            $key = $field['key'] ?? null;

            // The "sandbox" credential renders as a checkbox, so its value is
            // boolean rather than the string every other credential field holds.
            if ($key === 'sandbox') {
                $rules["required_fields.{$index}.value"] = ['boolean'];

                continue;
            }

            $rules["required_fields.{$index}.value"] = ['nullable', 'string'];

            if ($useOwn && !str_contains((string) $key, 'sandbox') && !str_contains((string) $key, 'test')) {
                $rules["required_fields.{$index}.value"][] = 'required';
            }
        }

        return $rules;
    }

    protected function booleanFields(): array
    {
        return ['use_own', 'sandbox_mode'];
    }
}
