<?php

namespace App\Contracts;

/**
 * Every storefront template must implement this contract.
 *
 * Adding a new template:
 *  1. Create a class in app/Services/Tenant/Templates/ that implements this interface.
 *  2. Register it in TemplateRegistryService::$strategies using the template slug as key.
 *  3. Seed the Theme record in a tenant migration and add it to TenantPanelService::seedThemes().
 *  4. Place Blade views at  resources/views/themes/{slug}/layout/  and  /pages/.
 */
interface TemplateStrategy
{
    /** Unique slug that matches the Theme.slug column. */
    public function slug(): string;

    /** Human-readable name shown in the Themes admin panel. */
    public function name(): string;

    /** Optional public path to a preview image (relative to public/). */
    public function previewPath(): ?string;

    /**
     * Blade view name for the Livewire full-page layout.
     * e.g.  'themes.ecommet.layout.app'
     */
    public function layout(): string;

    /**
     * Resolve the Blade view name for a named storefront page.
     * Page slugs are defined by supportedPages().
     * e.g.  pageView('home')  →  'themes.ecommet.pages.home'
     */
    public function pageView(string $page): string;

    /**
     * List of page slugs this template supports.
     * These map directly to route → Livewire component pairing.
     *
     * @return string[]
     */
    public function supportedPages(): array;
}
