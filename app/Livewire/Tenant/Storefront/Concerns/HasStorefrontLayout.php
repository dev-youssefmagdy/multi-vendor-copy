<?php

namespace App\Livewire\Tenant\Storefront\Concerns;

use App\Repositories\Tenant\StorefrontRepository;
use App\Services\Tenant\CustomerCountryResolver;
use App\Services\Tenant\TemplateRegistryService;
use App\Livewire\Tenant\Storefront\Concerns\InteractsWithStorefrontUi;

/**
 * Shared state + helpers for all storefront Livewire components.
 *
 * Usage: use HasStorefrontLayout in a Livewire component.
 * The component MUST call $this->bootStorefrontLayout() at the start of mount().
 */
trait HasStorefrontLayout
{
    use InteractsWithStorefrontUi;

    public bool $mobileMenuOpen = false;

    /** Resolve the ecommet template strategy once and cache it on the component. */
    protected function resolveStrategy(): \App\Contracts\TemplateStrategy
    {
        return app(TemplateRegistryService::class)->active();
    }

    /** Convenience: resolve the layout view string. */
    protected function storefrontLayout(): string
    {
        return $this->resolveStrategy()->layout();
    }

    /** Convenience: resolve a specific page view string. */
    protected function pageView(string $page): string
    {
        return $this->resolveStrategy()->pageView($page);
    }

    /**
     * Page-level data passed into the Livewire page view.
     * Layout-level data (logo, categories, social links, locale…) is now
     * handled by StorefrontComposer and shared automatically with every
     * themes.* view — no need to repeat it here.
     */
    protected function sharedData(): array
    {
        $repo = app(StorefrontRepository::class);

        $customerCountryId = app(CustomerCountryResolver::class)->resolveId();

        return [
            // currentCurrency is needed by _product-card for price conversion
            'currentCurrency' => $repo->currentCurrency(),
            // customerCountryId is used to resolve per-product fixed shipping costs
            'customerCountryId' => $customerCountryId,
        ];
    }

    public function toggleMobileMenu(): void
    {
        $this->mobileMenuOpen = !$this->mobileMenuOpen;
    }
}
