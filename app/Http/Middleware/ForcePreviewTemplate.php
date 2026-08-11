<?php

namespace App\Http\Middleware;

use App\Models\Tenant\Theme;
use App\Services\Tenant\TemplateRegistryService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Allows the central admin to preview any installed template on the
 * dedicated preview tenant without permanently changing its active theme.
 *
 * Only activates when ALL of the following conditions are met:
 *  1. A `?_tpl={slug}` query parameter is present.
 *  2. The current tenant has slug `preview` (the dedicated preview tenant).
 *  3. The requested slug is a registered template strategy.
 *
 * The override is request-scoped (static flag on TemplateRegistryService).
 */
class ForcePreviewTemplate
{
    public function handle(Request $request, Closure $next): Response
    {
        $slug = (string) $request->query('_tpl', '');

        if (
            $slug !== ''
            && $this->isPreviewTenant()
            && array_key_exists($slug, TemplateRegistryService::all())
        ) {
            TemplateRegistryService::force($slug);

            Theme::query()->whereSlug($slug)->update(['is_active' => 1]);
            Theme::query()->where('slug', '!=', $slug)->update(['is_active' => 0]);
        }

        return $next($request);
    }

    private function isPreviewTenant(): bool
    {
        try {
            $tenant = tenancy()->tenant;
            return $tenant !== null && ($tenant->slug ?? '') === 'preview';
        } catch (\Throwable) {
            return false;
        }
    }
}
