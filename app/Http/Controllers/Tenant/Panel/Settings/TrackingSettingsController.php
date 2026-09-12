<?php

declare(strict_types=1);

// ROUTES:
// Route::put('/tracking', [TrackingSettingsController::class, 'update'])->name('tracking.update');
// Route::post('/tracking/validate', [TrackingSettingsController::class, 'validateUpdate'])->name('tracking.validate');
// (both under the existing tenant.permission:settings.tracking.manage middleware, next to the kept `tracking` GET route)

namespace App\Http\Controllers\Tenant\Panel\Settings;

use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Http\Requests\Tenant\Panel\Settings\SaveTrackingSettingsRequest;
use App\Models\Tenant\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

final class TrackingSettingsController extends PanelController
{
    public function index(): View
    {
        $settings = Setting::query()
            ->whereIn('name', ['tracking_fb_pixel_id', 'tracking_tiktok_pixel_id', 'tracking_snapchat_pixel_id', 'tracking_ga_measurement_id'])
            ->pluck('value', 'name');

        return view('tenant.pages.settings.tracking.index', [
            'values' => [
                'fb_pixel_id' => (string) ($settings['tracking_fb_pixel_id'] ?? ''),
                'tiktok_pixel_id' => (string) ($settings['tracking_tiktok_pixel_id'] ?? ''),
                'snapchat_pixel_id' => (string) ($settings['tracking_snapchat_pixel_id'] ?? ''),
                'ga_measurement_id' => (string) ($settings['tracking_ga_measurement_id'] ?? ''),
            ],
        ]);
    }

    public function update(SaveTrackingSettingsRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $values = [
            'tracking_fb_pixel_id' => $validated['fb_pixel_id'] ?? '',
            'tracking_tiktok_pixel_id' => $validated['tiktok_pixel_id'] ?? '',
            'tracking_snapchat_pixel_id' => $validated['snapchat_pixel_id'] ?? '',
            'tracking_ga_measurement_id' => $validated['ga_measurement_id'] ?? '',
        ];

        foreach ($values as $name => $value) {
            Setting::query()->updateOrCreate(
                ['name' => $name],
                ['value' => (string) $value, 'group' => 'tracking'],
            );
        }

        return $this->success('Tracking settings saved successfully.');
    }

    public function validateUpdate(SaveTrackingSettingsRequest $request): JsonResponse
    {
        return $this->validFormResponse();
    }
}
