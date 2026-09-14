<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Store;

use App\Http\Requests\Tenant\Panel\TenantFormRequest;
use App\Repositories\Tenant\TenantPanelRepository;

final class SaveBannerRequest extends TenantFormRequest
{
    public function rules(): array
    {
        $rules = [
            'url' => ['nullable', 'url', 'max:500'],
            'serial_number' => ['required', 'integer', 'min:0'],
            'banner_image' => ['nullable', 'image', 'max:2048'],
            'country_id' => ['nullable', 'integer'],
        ];

        $languages = app(TenantPanelRepository::class)->activeLanguages();

        foreach ($languages as $language) {
            $rules["translations.{$language->code}.title"] = ['nullable', 'string', 'max:255'];
            $rules["translations.{$language->code}.subtitle"] = ['nullable', 'string', 'max:500'];
            $rules["translations.{$language->code}.button_text"] = ['nullable', 'string', 'max:100'];
        }

        return $rules;
    }

    public function attributes(): array
    {
        return [
            'serial_number' => 'sort order',
            'banner_image' => 'banner image',
        ];
    }
}
