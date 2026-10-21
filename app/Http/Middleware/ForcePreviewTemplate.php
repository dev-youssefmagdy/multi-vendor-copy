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
        $this->apply($request);

        return $next($request);
    }

    /**
     * Apply the preview overrides carried by $request's query string. Also used
     * for Livewire updates, which replay the query of the page that issued them.
     */
    public function apply(Request $request): void
    {
        if (!$this->isPreviewTenant()) {
            return;
        }

        PreviewOverrides::activate();
        $this->aliasStorefrontEndpoints();

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

        $sectionsRaw = $request->query('sections');
        if (filled($sectionsRaw)) {
            $keys = array_values(array_filter(array_map('trim', explode(',', (string) $sectionsRaw))));
            if (!empty($keys)) {
                PreviewOverrides::setSections($keys);
            }
        }
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

    /**
     * The storefront markup builds script endpoints from tenant.storefront.* route
     * names, which only resolve to tenant-host URLs. Point those names at the
     * /preview/* equivalents for this request.
     */
    private function aliasStorefrontEndpoints(): void
    {
        $routes = app('router')->getRoutes();

        foreach ([
            'preview.search.autocomplete' => 'tenant.storefront.search.autocomplete',
            'preview.products.json' => 'tenant.storefront.products.json',
            'preview.home.tabbed-products' => 'tenant.storefront.home.tabbed-products',
            'preview.category.products.json' => 'tenant.storefront.category.products.json',
        ] as $previewName => $tenantName) {
            $tenantRoute = $routes->getByName($tenantName);
            $tenantRoute?->setAction(array_merge($tenantRoute->getAction(), ['as' => $tenantName.'.shadowed']));

            $route = $routes->getByName($previewName);
            $route?->setAction(array_merge($route->getAction(), ['as' => $tenantName]));
        }

        $routes->refreshNameLookups();
    }
}
