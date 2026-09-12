<?php

declare(strict_types=1);

namespace App\Support\Tenant;

use BackedEnum;
use Illuminate\Support\Collection;

final class Options
{
    /**
     * @return array<int, array{value: mixed, label: string, disabled?: bool, group?: string, level?: int, image?: string}>
     */
    public static function normalize(mixed $options, ?string $valueKey = null, ?string $labelKey = null): array
    {
        if (is_string($options) && enum_exists($options) && is_subclass_of($options, BackedEnum::class)) {
            return collect($options::cases())
                ->map(fn (BackedEnum $case) => [
                    'value' => $case->value,
                    'label' => method_exists($case, 'label') ? $case->label() : str($case->name)->headline()->toString(),
                ])
                ->all();
        }

        $collection = $options instanceof Collection ? $options : collect($options);

        return $collection
            ->map(fn ($option, $key) => self::normalizeOne($option, $key, $valueKey, $labelKey))
            ->values()
            ->all();
    }

    private static function normalizeOne(mixed $option, mixed $key, ?string $valueKey, ?string $labelKey, int $level = 0): array
    {
        if (is_array($option) || is_object($option)) {
            $row = (array) $option;

            $value = $valueKey ? data_get($row, $valueKey) : ($row['value'] ?? $row['id'] ?? $key);
            $label = $labelKey ? data_get($row, $labelKey) : ($row['label'] ?? $row['name'] ?? $row['title'] ?? (string) $value);

            $normalized = [
                'value' => $value,
                'label' => $label,
                'level' => $level,
            ];

            if (isset($row['disabled'])) {
                $normalized['disabled'] = (bool) $row['disabled'];
            }

            if (isset($row['group'])) {
                $normalized['group'] = $row['group'];
            }

            if (isset($row['image'])) {
                $normalized['image'] = $row['image'];
            }

            return $normalized;
        }

        return [
            'value' => is_int($key) ? $option : $key,
            'label' => (string) $option,
            'level' => $level,
        ];
    }

    /**
     * Flattens a nested tree (children key) into a leveled option list.
     */
    public static function fromTree(iterable $nodes, string $labelKey = 'name', string $childrenKey = 'children', int $level = 0): array
    {
        $result = [];

        foreach ($nodes as $node) {
            $row = (array) $node;

            $result[] = [
                'value' => $row['id'] ?? $row['value'] ?? null,
                'label' => $row[$labelKey] ?? '',
                'level' => $level,
            ];

            if (!empty($row[$childrenKey])) {
                $result = array_merge($result, self::fromTree($row[$childrenKey], $labelKey, $childrenKey, $level + 1));
            }
        }

        return $result;
    }
}
