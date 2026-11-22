<?php

namespace App\Support\Catalog;

/**
 * Integer presentation key of a MERGED category (dedup plan phase 4, §11 "Identity"): the `id` merged categories
 * expose to views, themes, JS and the mobile API, which use it as a plain integer (unquoted JS arguments, HTML ids,
 * `?category_id=` links, Livewire `wire:key`).
 *
 *  - central category  => its central id                       (live tenants: equal to today's copy id)
 *  - own category      => its tenant id + OWN_OFFSET (1e9)     (central ids never get there)
 *
 * The key is NEVER persisted: storage uses typed columns / CatalogRef strings ("c:12" / "t:3"). Every `category_id`
 * input accepts a key or a ref (parse()).
 */
final class CategoryKey
{
    public const OWN_OFFSET = 1_000_000_000;

    public static function of(CatalogRef $ref): int
    {
        return $ref->isCentral() ? $ref->id : $ref->id + self::OWN_OFFSET;
    }

    public static function ref(int $key): ?CatalogRef
    {
        if ($key < 1) {
            return null;
        }

        return $key >= self::OWN_OFFSET ? CatalogRef::tenant($key - self::OWN_OFFSET) : CatalogRef::central($key);
    }

    /** Key (int / numeric string) or ref string ("c:12" / "t:3") → ref; anything else → null. */
    public static function parse(mixed $value): ?CatalogRef
    {
        if ($value instanceof CatalogRef) {
            return $value;
        }
        if (is_int($value)) {
            return self::ref($value);
        }
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);
        if (preg_match('/^[1-9][0-9]{0,17}$/', $value)) {
            return self::ref((int) $value);
        }

        return CatalogRef::tryParse($value);
    }

    /** Key of a key-or-ref value, or null. */
    public static function keyOf(mixed $value): ?int
    {
        $ref = self::parse($value);

        return $ref === null ? null : self::of($ref);
    }
}
