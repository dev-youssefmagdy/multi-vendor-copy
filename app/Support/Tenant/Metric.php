<?php

declare(strict_types=1);

namespace App\Support\Tenant;

final class Metric
{
    /**
     * @param array<int, array{label:string, value:mixed, caption?:string, dot?:string, glow?:string, format?:string, suffix?:string}> $cards
     */
    public static function cards(array $cards): array
    {
        return collect($cards)
            ->map(function (array $card) {
                $card['value'] = self::formatValue(
                    $card['value'] ?? 0,
                    $card['format'] ?? null,
                    $card['suffix'] ?? null,
                );

                return $card;
            })
            ->all();
    }

    public static function formatValue(mixed $value, ?string $format = null, ?string $suffix = null): string
    {
        $formatted = match ($format) {
            'currency' => '$'.number_format((float) $value, 2),
            'percent' => number_format((float) $value, 2).'%',
            'number' => fmod((float) $value, 1.0) === 0.0
                ? number_format((float) $value, 0)
                : number_format((float) $value, 2),
            default => (string) $value,
        };

        if ($suffix) {
            return $formatted.' '.$suffix;
        }

        return $formatted;
    }
}
