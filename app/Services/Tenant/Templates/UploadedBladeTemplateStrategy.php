<?php

namespace App\Services\Tenant\Templates;

use App\Contracts\TemplateStrategy;

/**
 * Strategy for a vendor's admin-approved, uploaded Blade theme.
 *
 * Unlike the code-shipped strategies (Elora/Souqify/Ecommet), this one
 * doesn't point at resources/views/themes/{slug} — it relies on
 * IdentifyTenantTheme middleware having already prepended the tenant's live
 * theme directory (storage/app/tenants/{id}/theme/views/) to the view
 * finder, so plain unprefixed view names resolve to the vendor's files.
 */
class UploadedBladeTemplateStrategy implements TemplateStrategy
{
    public function slug(): string
    {
        return 'custom';
    }

    public function name(): string
    {
        return 'Custom Theme';
    }

    public function previewPath(): ?string
    {
        return null;
    }

    public function layout(): string
    {
        return 'layout.app';
    }

    public function pageView(string $page): string
    {
        return $page === 'home' ? 'pages.home.index' : "pages.{$page}";
    }

    public function supportedPages(): array
    {
        return [
            'home', 'product', 'category', 'cart', 'checkout',
            'order-status', 'order-tracking', 'order-return',
            'auth', 'profile', 'favorites', 'page',
            'best-selling', 'new-in', '404',
        ];
    }
}
