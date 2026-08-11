<?php

namespace App\Services\Tenant\Templates;

use App\Contracts\TemplateStrategy;

/**
 * Elora storefront template strategy.
 *
 * Views live at: resources/views/themes/elora/
 * Static assets:  public/elora/assets/
 */
class EloraTemplateStrategy implements TemplateStrategy
{
    public function slug(): string
    {
        return 'elora';
    }

    public function name(): string
    {
        return 'Elora';
    }

    public function previewPath(): ?string
    {
        return 'elora/assets/images/preview.png';
    }

    public function layout(): string
    {
        return 'themes.elora.layout.app';
    }

    public function pageView(string $page): string
    {
        return 'themes.elora.pages.' . $page;
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
