<?php

declare(strict_types=1);

namespace App\Support\Tenant\Storefront;

use App\Services\Tenant\TemplateRegistryService;
use Throwable;

/**
 * Visual tokens for the shared after-sales storefront partials (cancel modal, cancellation
 * banner, refund cards, return form / detail). The partials are shared by every theme, so they
 * take the colours / radii of the active theme from here and apply them as inline styles — that
 * keeps them independent of each theme's compiled Tailwind build.
 */
final class AfterSalesUi
{
    /** @var array<string, array<string, string>> */
    private const THEMES = [
        'ecommet' => [
            'accent' => '#FF4D00',
            'accent_hover' => '#E64500',
            'accent_soft' => '#FFF5F2',
            'accent_border' => '#FFAC88',
            'dark' => '#242424',
            'text' => '#242424',
            'muted' => '#808080',
            'border' => '#EEEEEE',
            'surface' => '#FAFAFA',
            'card_radius' => '12px',
            'button_radius' => '9999px',
            'font' => 'inherit',
        ],
        'elora' => [
            'accent' => '#FF4D00',
            'accent_hover' => '#E64500',
            'accent_soft' => '#FFF3EE',
            'accent_border' => '#FF4D00',
            'dark' => '#242424',
            'text' => '#242424',
            'muted' => '#808080',
            'border' => '#E5E7EB',
            'surface' => '#FFFFFF',
            'card_radius' => '16px',
            'button_radius' => '32px',
            'font' => "'Outfit', sans-serif",
        ],
        'souqify' => [
            'accent' => '#0159ED',
            'accent_hover' => '#1E40AF',
            'accent_soft' => '#F6F8FF',
            'accent_border' => '#0159ED',
            'dark' => '#242424',
            'text' => '#262626',
            'muted' => '#71717A',
            'border' => '#E5E7EB',
            'surface' => '#FFFFFF',
            'card_radius' => '16.4px',
            'button_radius' => '32px',
            'font' => "'Outfit', sans-serif",
        ],
        'default' => [
            'accent' => '#111111',
            'accent_hover' => '#000000',
            'accent_soft' => '#F5F5F5',
            'accent_border' => '#D4D4D4',
            'dark' => '#111111',
            'text' => '#171717',
            'muted' => '#737373',
            'border' => '#E5E5E5',
            'surface' => '#FFFFFF',
            'card_radius' => '12px',
            'button_radius' => '9999px',
            'font' => 'inherit',
        ],
    ];

    /** Badge colours for the enum colour names used by OrderStatus / RefundStatus / ReturnStatus / InspectionResult. */
    private const BADGES = [
        'green' => ['bg' => '#DCFCE7', 'text' => '#15803D', 'border' => '#BBF7D0'],
        'blue' => ['bg' => '#DBEAFE', 'text' => '#1D4ED8', 'border' => '#BFDBFE'],
        'red' => ['bg' => '#FEE2E2', 'text' => '#DC2626', 'border' => '#FECACA'],
        'amber' => ['bg' => '#FEF3C7', 'text' => '#B45309', 'border' => '#FDE68A'],
        'orange' => ['bg' => '#FFEDD5', 'text' => '#C2410C', 'border' => '#FED7AA'],
        'violet' => ['bg' => '#EDE9FE', 'text' => '#6D28D9', 'border' => '#DDD6FE'],
        'gray' => ['bg' => '#F3F4F6', 'text' => '#4B5563', 'border' => '#E5E7EB'],
    ];

    /** @return array<string, string> */
    public static function tokens(?string $theme = null): array
    {
        $theme ??= self::activeTheme();

        return self::THEMES[$theme] ?? self::THEMES['default'];
    }

    /** @return array{bg: string, text: string, border: string} */
    public static function badge(?string $color): array
    {
        return self::BADGES[$color ?? ''] ?? self::BADGES['amber'];
    }

    public static function activeTheme(): string
    {
        try {
            return app(TemplateRegistryService::class)->active()->slug();
        } catch (Throwable) {
            return 'default';
        }
    }
}
