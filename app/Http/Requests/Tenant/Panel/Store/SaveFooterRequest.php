<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Store;

use App\Http\Requests\Tenant\Panel\TenantFormRequest;
use App\Repositories\Tenant\TenantPanelRepository;

final class SaveFooterRequest extends TenantFormRequest
{
    public function rules(): array
    {
        $fontKeys = implode(',', array_keys(\App\Repositories\Tenant\StorefrontRepository::LOGO_FONTS['ar']))
            . ',' . implode(',', array_keys(\App\Repositories\Tenant\StorefrontRepository::LOGO_FONTS['en']));

        $rules = [
            'footer_logo_mode'      => ['required', 'in:text,image'],
            'footer_logo_text_ar'   => ['nullable', 'string', 'max:60'],
            'footer_logo_text_en'   => ['nullable', 'string', 'max:60'],
            'footer_logo_color'     => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'footer_logo_bg_color'  => ['required', 'string', 'regex:/^(#[0-9a-fA-F]{6}|transparent)$/'],
            'footer_logo_shape'     => ['required', 'in:rectangle,rounded'],
            'footer_logo_font_ar'   => ['required', 'in:' . $fontKeys],
            'footer_logo_font_en'   => ['required', 'in:' . $fontKeys],
            'footer_logo_upload_ar' => ['nullable', 'image', 'max:2048'],
            'footer_logo_upload_en' => ['nullable', 'image', 'max:2048'],
            'footer_logo_width'     => ['nullable', 'integer', 'min:20', 'max:800'],
        ];

        foreach (app(TenantPanelRepository::class)->activeLanguages() as $language) {
            $rules["translations.{$language->code}.footer_text"] = ['nullable', 'string', 'max:1000'];
            $rules["translations.{$language->code}.footer_copyright"] = ['nullable', 'string', 'max:255'];
        }

        return $rules;
    }

    public function attributes(): array
    {
        return [
            'footer_logo_mode'      => 'footer logo mode',
            'footer_logo_text_ar'   => 'Arabic footer logo text',
            'footer_logo_text_en'   => 'English footer logo text',
            'footer_logo_color'     => 'footer text color',
            'footer_logo_bg_color'  => 'footer background color',
            'footer_logo_shape'     => 'footer logo shape',
            'footer_logo_font_ar'   => 'Arabic footer font',
            'footer_logo_font_en'   => 'English footer font',
            'footer_logo_upload_ar' => 'Arabic footer logo image',
            'footer_logo_upload_en' => 'English footer logo image',
            'footer_logo_width'     => 'footer logo width',
        ];
    }
}
