<?php

namespace App\Services\Tenant\Templates;

use App\Contracts\TemplateStrategy;

/**
 * Ecommet storefront template strategy.
 *
 * Views live at: resources/views/themes/ecommet/
 * Static assets:  public/ecommet/assets/
 */
class EcommetTemplateStrategy implements TemplateStrategy
{
    public function slug(): string
    {
        return 'ecommet';
    }

    public function name(): string
    {
        return 'Ecommet';
    }

    public function previewPath(): ?string
    {
        return 'ecommet/assets/images/home/landingImg.png';
    }

    public function layout(): string
    {
        return 'themes.ecommet.layout.app';
    }

    public function pageView(string $page): string
    {
        return 'themes.ecommet.pages.' . $page;
    }

    public function supportedPages(): array
    {
        return [
            'home',
            'best-selling',
            'full-star',
            'new-in',
            'category',
            'product',
            'cart',
            'checkout',
            'order-status',
            'order-tracking',
            'profile',
            'auth',
        ];
    }
}
