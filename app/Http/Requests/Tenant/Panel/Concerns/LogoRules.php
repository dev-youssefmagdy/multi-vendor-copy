<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Concerns;

use App\Repositories\Tenant\StorefrontRepository;

/**
 * Shared logo validation rules for the `_logo-builder.blade.php` partial —
 * used by Store ▸ Appearance (General tab) and the Onboarding setup logo
 * item. Keep both FormRequests in sync by validating through this trait.
 */
trait LogoRules
{
    protected function logoRules(): array
    {
        return [
            'logo_mode' => ['required', 'in:text,image'],
            'logo_text_ar' => ['nullable', 'string', 'max:60'],
            'logo_text_en' => ['nullable', 'string', 'max:60'],
            'logo_color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'logo_bg_color' => ['required', 'string', 'regex:/^(#[0-9a-fA-F]{6}|transparent)$/'],
            'logo_shape' => ['required', 'in:rectangle,rounded'],
            'logo_font_ar' => ['required', 'in:' . implode(',', array_keys(StorefrontRepository::LOGO_FONTS['ar']))],
            'logo_font_en' => ['required', 'in:' . implode(',', array_keys(StorefrontRepository::LOGO_FONTS['en']))],
            'logo_upload_ar' => ['nullable', 'image', 'max:2048'],
            'logo_upload_en' => ['nullable', 'image', 'max:2048'],
        ];
    }

    protected function logoAttributes(): array
    {
        return [
            'logo_mode' => 'logo mode',
            'logo_text_ar' => 'Arabic logo text',
            'logo_text_en' => 'English logo text',
            'logo_color' => 'text color',
            'logo_bg_color' => 'background color',
            'logo_shape' => 'shape',
            'logo_font_ar' => 'Arabic font',
            'logo_font_en' => 'English font',
            'logo_upload_ar' => 'Arabic logo image',
            'logo_upload_en' => 'English logo image',
        ];
    }
}
