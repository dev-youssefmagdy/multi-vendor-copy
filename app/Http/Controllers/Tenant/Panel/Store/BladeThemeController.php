<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Store;

use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Http\Requests\Tenant\Panel\Store\UploadBladeThemeRequest;
use App\Models\BladeTheme;
use App\Services\Tenant\BladeThemeService;
use App\Support\Tenant\TableColumn;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;
use RuntimeException;
use Throwable;
use Illuminate\Support\Facades\Log;

final class BladeThemeController extends PanelController
{
    public function __construct(
        private readonly BladeThemeService $service,
    ) {
    }

    public function index(): View
    {
        $tenantId = (string) tenant()->getTenantKey();
        $themes = $this->service->themesForTenant($tenantId);

        $rows = $themes->map(fn (BladeTheme $theme) => [
            e($theme->version),
            e($theme->uploaded_at?->diffForHumans() ?? $theme->created_at->diffForHumans()),
            view('tenant.pages.store.blade-theme._cols.status', ['theme' => $theme])->render(),
            view('tenant.pages.store.blade-theme._cols.actions', ['theme' => $theme])->render(),
        ])->all();

        return view('tenant.pages.store.blade-theme.index', [
            'themes' => $themes,
            'activeTheme' => $themes->first(fn (BladeTheme $t) => $t->is_active),
            'rows' => $rows,
            'columns' => [
                TableColumn::make('version', 'Version')->orderable(false),
                TableColumn::make('uploaded_at', 'Uploaded')->orderable(false),
                TableColumn::make('status', 'Status')->orderable(false),
                TableColumn::actions(),
            ],
        ]);
    }

    public function upload(UploadBladeThemeRequest $request): JsonResponse
    {
        try {
            $this->service->upload(tenant(), $request->file('theme_zip'));
        } catch (RuntimeException $e) {
            return $this->failure($e->getMessage(), 422, ['theme_zip' => [$e->getMessage()]]);
        } catch (Throwable $e) {
            Log::error('Blade theme upload failed', ['error' => $e->getMessage()]);

            $message = 'The theme could not be processed. Please check the ZIP contents and try again.';

            return $this->failure($message, 422, ['theme_zip' => [$message]]);
        }

        return $this->success('Theme uploaded and queued for admin review.');
    }

    public function validateUpload(UploadBladeThemeRequest $request): JsonResponse
    {
        return $this->validFormResponse();
    }

    public function deactivate(): JsonResponse
    {
        $this->service->deactivate((string) tenant()->getTenantKey());

        return $this->success('Blade theme deactivated. Your storefront now uses the system theme again.');
    }

    public function destroy(int $upload): JsonResponse
    {
        tenancy()->central(function () use ($upload): void {
            $tenantId = (string) tenant()->getTenantKey();
            $theme = BladeTheme::query()->where('tenant_id', $tenantId)->findOrFail($upload);

            if ($theme->is_active) {
                return;
            }

            $path = storage_path('app/'.$theme->storage_path);
            if (is_dir($path)) {
                File::deleteDirectory($path);
            }

            $theme->delete();
        });

        return $this->success('Theme deleted.');
    }
}
