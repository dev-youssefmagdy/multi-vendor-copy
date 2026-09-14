<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Settings;

use App\Enums\ActivationStatus;
use App\Http\Requests\Tenant\Panel\TenantFormRequest;
use Illuminate\Validation\Rule;

final class SaveAdminRequest extends TenantFormRequest
{
    public function rules(): array
    {
        $adminId = $this->route('admin')?->id;

        return [
            'role_id' => ['nullable', 'integer', 'exists:admin_roles,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('admins', 'email')->ignore($adminId)],
            'password' => [$adminId ? 'nullable' : 'required', 'string', 'min:8'],
            'status' => ['required', Rule::enum(ActivationStatus::class)],
        ];
    }

    public function attributes(): array
    {
        return [
            'role_id' => 'role',
        ];
    }
}
