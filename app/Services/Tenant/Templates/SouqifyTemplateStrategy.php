<?php

namespace App\Services\Tenant\Templates;

use App\Contracts\TemplateStrategy;
use App\Support\ThemeColorKeys;

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

    public function colorDefaults(): array
    {
        return [
            ThemeColorKeys::PRIMARY => '#0159ED',
            ThemeColorKeys::SECONDARY => '#001537',
            ThemeColorKeys::BACKGROUND => '#ffffff',
            ThemeColorKeys::TEXT => '#001537',
            ThemeColorKeys::ACCENT => '#FFE100',
            ThemeColorKeys::HEADER_BG => '#001537',
            ThemeColorKeys::FOOTER_BG => '#001537',
        ];
    }
}
