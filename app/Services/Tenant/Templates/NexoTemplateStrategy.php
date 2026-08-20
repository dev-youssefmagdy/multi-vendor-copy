<?php

namespace App\Services\Tenant\Templates;

use App\Contracts\TemplateStrategy;

/**
 * Nexo storefront template strategy.
 *
 * Views live at: resources/views/themes/nexo/
 * Static assets:  public/nexo/assets/
 */
class NexoTemplateStrategy implements TemplateStrategy
{
    public function slug(): string
    {
        return 'nexo';
    }

    public function name(): string
    {
        return 'Nexo';
    }

    public function previewPath(): ?string
    {
        return 'nexo/assets/images/preview.png';
    }

    public function layout(): string
    {
        return 'themes.nexo.layout.app';
    }

    public function pageView(string $page): string
    {
        return 'themes.nexo.pages.' . $page;
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
            'order-tracking',
            'order-return',
            'profile',
            'auth',
            'favorites',
            'page',
            '404',
        ];
    }
}
