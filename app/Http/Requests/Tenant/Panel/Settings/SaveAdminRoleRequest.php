<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Settings;

use App\Http\Requests\Tenant\Panel\TenantFormRequest;
use App\Repositories\Tenant\TenantPanelRepository;
use Illuminate\Validation\Rule;

final class SaveAdminRoleRequest extends TenantFormRequest
{
    public function rules(): array
    {
        $roleId = $this->route('role')?->id;
        $availablePermissions = array_keys(app(TenantPanelRepository::class)->availableAdminPermissions());

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('admin_roles', 'name')->ignore($roleId)],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in($availablePermissions)],
        ];
    }
}
