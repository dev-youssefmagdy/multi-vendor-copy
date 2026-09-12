<?php

declare(strict_types=1);

namespace App\Support\Tenant;

final class JsonTreeBuilder
{
    /**
     * Convert an arbitrary array/scalar payload into the node tree shape
     * expected by the x-tenant::json-tree component.
     *
     * @return array<int, array{key:string, type:string, children?:array, desc?:string|null}>
     */
    public static function nodes(mixed $value): array
    {
        if (is_array($value)) {
            return collect($value)
                ->map(fn ($item, $key) => self::node((string) $key, $item))
                ->values()
                ->all();
        }

        return [self::node('value', $value)];
    }

    private static function node(string $key, mixed $value): array
    {
        if (is_array($value)) {
            $isList = array_is_list($value);

            return [
                'key' => $key,
                'type' => $isList ? 'array' : 'object',
                'children' => self::nodes($value),
            ];
        }

        return [
            'key' => $key,
            'type' => self::type($value),
            'desc' => is_scalar($value) || $value === null ? self::describe($value) : null,
        ];
    }

    private static function type(mixed $value): string
    {
        return match (true) {
            $value === null => 'null',
            is_bool($value) => 'bool',
            is_int($value) => 'int',
            is_float($value) => 'float',
            is_string($value) => 'string',
            default => 'mixed',
        };
    }

    private static function describe(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return (string) $value;
    }
}
