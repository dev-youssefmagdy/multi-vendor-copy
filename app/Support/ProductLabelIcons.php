<?php

namespace App\Support;

/**
 * Icon registry for product labels. Each icon is the inner markup of a 24x24
 * stroke icon, so every theme card can drop it into its own <svg> and recolour it
 * through currentColor. The admin stores only the key.
 */
class ProductLabelIcons
{
    /** @return array<string, array{label: string, svg: string}> */
    public static function all(): array
    {
        return [
            'truck' => ['label' => 'Truck', 'svg' => '<path d="M1 3h15v13H1zM16 8h4l3 3v5h-7z"/><circle cx="5.5" cy="18.5" r="2"/><circle cx="18.5" cy="18.5" r="2"/>'],
            'package' => ['label' => 'Package (free shipping)', 'svg' => '<path d="m7.5 4.3 9 5.2M21 8v8l-9 5-9-5V8l9-5z"/><path d="M3.3 7 12 12l8.7-5M12 22V12"/>'],
            'bolt' => ['label' => 'Lightning', 'svg' => '<path d="M13 2 3 14h9l-1 8 10-12h-9z"/>'],
            'clock' => ['label' => 'Clock', 'svg' => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>'],
            'fire' => ['label' => 'Fire', 'svg' => '<path d="M12 22c4 0 7-3 7-7 0-3-2-5-3-7-1 2-2 3-3 3 0-3-1-6-4-9 0 4-4 6-4 12 0 4 3 8 7 8z"/>'],
            'box' => ['label' => 'Box', 'svg' => '<path d="M21 8 12 3 3 8v8l9 5 9-5zM3 8l9 5 9-5M12 13v8"/>'],
            'shield' => ['label' => 'Shield check', 'svg' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/>'],
            'gift' => ['label' => 'Gift', 'svg' => '<path d="M20 12v10H4V12M2 7h20v5H2zM12 22V7M12 7H7.5a2.5 2.5 0 1 1 0-5C11 2 12 7 12 7zM12 7h4.5a2.5 2.5 0 1 0 0-5C13 2 12 7 12 7z"/>'],
            'tag' => ['label' => 'Tag', 'svg' => '<path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L2 12V2h10l8.6 8.6a2 2 0 0 1 0 2.8z"/><circle cx="7" cy="7" r="1.5"/>'],
            'percent' => ['label' => 'Discount', 'svg' => '<path d="M19 5 5 19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/>'],
            'star' => ['label' => 'Star', 'svg' => '<path d="m12 2 3.1 6.3 6.9 1-5 4.9 1.2 6.8L12 17.8 5.8 21l1.2-6.8-5-4.9 6.9-1z"/>'],
            'heart' => ['label' => 'Heart', 'svg' => '<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8l1 1.1L12 21.2l7.8-7.7 1-1.1a5.5 5.5 0 0 0 0-7.8z"/>'],
            'sparkles' => ['label' => 'Sparkles', 'svg' => '<path d="m12 3 1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9zM19 15l.8 2.2L22 18l-2.2.8L19 21l-.8-2.2L16 18l2.2-.8z"/>'],
            'award' => ['label' => 'Award', 'svg' => '<circle cx="12" cy="8" r="6"/><path d="m8.2 13.9-1.2 8.1 5-3 5 3-1.2-8.1"/>'],
            'undo' => ['label' => 'Free returns', 'svg' => '<path d="M3 7v6h6"/><path d="M21 17a9 9 0 0 0-15-6.7L3 13"/>'],
            'leaf' => ['label' => 'Eco', 'svg' => '<path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.5 19 2c1 2 2 4.2 2 8 0 5.5-4.8 10-10 10zM2 22c0-4 3-9 9-11"/>'],
            'globe' => ['label' => 'Worldwide', 'svg' => '<circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15 15 0 0 1 0 20 15 15 0 0 1 0-20z"/>'],
        ];
    }

    public static function svg(?string $key): ?string
    {
        return self::all()[$key]['svg'] ?? null;
    }

    /** @return array<string, string> key => label */
    public static function options(): array
    {
        return array_map(fn(array $i) => $i['label'], self::all());
    }
}
