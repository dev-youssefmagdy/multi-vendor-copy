<?php

namespace App\Http\Middleware;

use App\Models\Tenant\Theme;
use App\Services\Preview\PreviewOverrides;
use App\Services\Tenant\TemplateRegistryService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Drives the theme preview system on the dedicated "preview" tenant, letting
 * the tenant panel "Preview" button and the admin Templates page render the
 * storefront with URL-supplied overrides without ever touching real tenant
 * data:
 *
 *   /preview?theme=elora&homepage_variant=v2
 *
 * Only activates when the current tenant has slug `preview`. `theme` (or the
 * legacy `_tpl` alias) forces the template strategy and the tenant's
 * "active" theme row; `homepage_variant` forces which HomeVariant is used,
 * all for this request only.
 */
class ForcePreviewTemplate
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$this->isPreviewTenant()) {
            return $next($request);
        }

        PreviewOverrides::activate();

        $slug = (string) ($request->query('theme') ?: $request->query('_tpl', ''));

        if ($slug !== '' && array_key_exists($slug, TemplateRegistryService::all())) {
            TemplateRegistryService::force($slug);

            Theme::query()->whereSlug($slug)->update(['is_active' => 1]);
            Theme::query()->where('slug', '!=', $slug)->update(['is_active' => 0]);
        }

        $variant = $request->query('homepage_variant');
        if (filled($variant)) {
            PreviewOverrides::setHomepageVariantKey((string) $variant);
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
