<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Store;

use App\Http\Requests\Tenant\Panel\TenantFormRequest;

final class UploadBladeThemeRequest extends TenantFormRequest
{
    public function rules(): array
    {
        return [
            'theme_zip' => ['required', 'file', 'mimes:zip', 'max:20480'],
        ];
    }

    public function attributes(): array
    {
        return [
            'theme_zip' => 'theme ZIP',
        ];
    }
}
