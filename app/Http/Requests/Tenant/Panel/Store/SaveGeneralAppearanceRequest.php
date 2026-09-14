<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Store;

use App\Http\Requests\Tenant\Panel\Concerns\LogoRules;
use App\Http\Requests\Tenant\Panel\TenantFormRequest;

final class SaveGeneralAppearanceRequest extends TenantFormRequest
{
    use LogoRules;

    public function rules(): array
    {
        return $this->logoRules();
    }

    public function attributes(): array
    {
        return $this->logoAttributes();
    }
}
