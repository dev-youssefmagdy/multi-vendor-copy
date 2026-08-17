<?php

namespace App\Services\Tenant\Templates;

use App\Contracts\TemplateStrategy;

/**
 * Souqify storefront template strategy.
 *
 * Views live at: resources/views/themes/souqify/
 * Static assets: public/souqify/assets/
 *
 * Brand palette: navy blue (#0159ED / #001537) + gold accent (#FFE100).
 */
class SouqifyTemplateStrategy implements TemplateStrategy
{
    public function slug(): string
    {
        return 'souqify';
    }

    public function name(): string
    {
        return 'Souqify';
    }

    public function previewPath(): ?string
    {
        return 'souqify/assets/images/preview.png';
    }

    public function layout(): string
    {
        return 'themes.souqify.layout.app';
    }

    public function pageView(string $page): string
    {
        return 'themes.souqify.pages.' . $page;
    }

    public function supportedPages(): array
    {
        return [
            'home',
            'best-selling',
            'new-in',
            'category',
            'product',
            'cart',
            'checkout',
            'order-status',
            'profile',
            'auth',
        ];
    }
}
