<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Store;

use App\Http\Requests\Tenant\Panel\TenantFormRequest;
use App\Repositories\Tenant\TenantPanelRepository;

final class SaveColorsRequest extends TenantFormRequest
{
    public function rules(): array
    {
        $theme = (int) $this->route('theme');
        $variant = (int) $this->route('variant');

        $defaults = app(TenantPanelRepository::class)->themeVariantColorDefaults($theme, $variant);

        $rules = [];
        foreach (array_keys($defaults) as $property) {
            $rules["values.{$property}"] = ['required', 'string', 'regex:/^#[0-9a-fA-F]{3,8}$/'];
        }

        return $rules;
    }
}
