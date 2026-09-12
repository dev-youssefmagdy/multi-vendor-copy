<?php

declare(strict_types=1);

namespace App\Support\Tenant;

final class FieldName
{
    public static function html(string $dot): string
    {
        $parts = explode('.', $dot);
        $first = array_shift($parts);

        if ($parts === []) {
            return $first;
        }

        return $first.'['.implode('][', $parts).']';
    }

    public static function dot(string $html): string
    {
        return str_replace(['[', ']'], ['.', ''], $html);
    }

    public static function id(string $dot, ?string $suffix = null): string
    {
        $id = 'f-'.str_replace('.', '-', $dot);

        return $suffix ? $id.'-'.$suffix : $id;
    }
}
