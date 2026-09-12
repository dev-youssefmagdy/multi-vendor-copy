<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Settings;

use App\Http\Requests\Tenant\Panel\TenantFormRequest;

final class SaveTrackingSettingsRequest extends TenantFormRequest
{
    public function rules(): array
    {
        return [
            'fb_pixel_id' => ['nullable', 'string', 'max:64'],
            'tiktok_pixel_id' => ['nullable', 'string', 'max:64'],
            'snapchat_pixel_id' => ['nullable', 'string', 'max:64'],
            'ga_measurement_id' => ['nullable', 'string', 'max:32', 'regex:/^G-[A-Z0-9]+$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'ga_measurement_id.regex' => 'The GA4 Measurement ID must look like G-XXXXXXXX.',
        ];
    }

    public function attributes(): array
    {
        return [
            'fb_pixel_id' => 'Facebook pixel ID',
            'tiktok_pixel_id' => 'TikTok pixel ID',
            'snapchat_pixel_id' => 'Snapchat pixel ID',
            'ga_measurement_id' => 'GA4 measurement ID',
        ];
    }
}
