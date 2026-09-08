<?php

namespace App\Livewire\Tenant\Setting;

use App\Livewire\Tenant\Base\TenantPage;
use App\Livewire\Tenant\Concerns\InteractsWithTenantUi;
use App\Models\Tenant\Setting;

class TrackingSettingsPage extends TenantPage
{
    use InteractsWithTenantUi;

    public string $fbPixelId = '';
    public string $tiktokPixelId = '';
    public string $snapchatPixelId = '';
    public string $gaMeasurementId = '';

    public function mount(): void
    {
        $settings = Setting::query()
            ->whereIn('name', ['tracking_fb_pixel_id', 'tracking_tiktok_pixel_id', 'tracking_snapchat_pixel_id', 'tracking_ga_measurement_id'])
            ->pluck('value', 'name');

        $this->fbPixelId = (string) ($settings['tracking_fb_pixel_id'] ?? '');
        $this->tiktokPixelId = (string) ($settings['tracking_tiktok_pixel_id'] ?? '');
        $this->snapchatPixelId = (string) ($settings['tracking_snapchat_pixel_id'] ?? '');
        $this->gaMeasurementId = (string) ($settings['tracking_ga_measurement_id'] ?? '');
    }

    protected function pageView(): string
    {
        return 'livewire.tenant.setting.tracking-settings';
    }

    protected function pageMeta(): array
    {
        return [
            'title' => 'Tracking',
            'badge' => 'Settings',
            'description' => 'Connect Facebook, TikTok, Snapchat pixels and Google Analytics to your storefront.',
        ];
    }

    public function save(): void
    {
        $this->validate([
            'fbPixelId' => ['nullable', 'string', 'max:64'],
            'tiktokPixelId' => ['nullable', 'string', 'max:64'],
            'snapchatPixelId' => ['nullable', 'string', 'max:64'],
            'gaMeasurementId' => ['nullable', 'string', 'max:32', 'regex:/^G-[A-Z0-9]+$/'],
        ], [
            'gaMeasurementId.regex' => 'The GA4 Measurement ID must look like G-XXXXXXXX.',
        ]);

        $values = [
            'tracking_fb_pixel_id' => $this->fbPixelId,
            'tracking_tiktok_pixel_id' => $this->tiktokPixelId,
            'tracking_snapchat_pixel_id' => $this->snapchatPixelId,
            'tracking_ga_measurement_id' => $this->gaMeasurementId,
        ];

        foreach ($values as $name => $value) {
            Setting::query()->updateOrCreate(
                ['name' => $name],
                ['value' => (string) $value, 'group' => 'tracking']
            );
        }

        $this->toast('Tracking settings saved successfully.');
    }
}
