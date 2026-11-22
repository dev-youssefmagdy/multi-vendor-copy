<?php

namespace App\Support\Catalog;

/**
 * Integer presentation key of a MERGED product or variant (dedup plan phase 5, same scheme as CategoryKey): the `id`
 * merged rows expose to views, themes, JS, carts and the mobile API.
 *
 *  - central product / variant => its central id
 *  - own product / variant     => its tenant id + OWN_OFFSET (1e9)
 *
 * Never persisted: storage uses the phase-1 typed pairs (`product_type` + `catalog_product_id`) or CatalogRef strings
 * ("c:12" / "t:3"). ALL key / ref resolution lives here (PHP and SQL), so no join invents its own logic.
 *
 * SQL helpers return fragments with identifiers only (no bindings).
 */
final class ProductKey
{
    public const OWN_OFFSET = CategoryKey::OWN_OFFSET;

    public static function of(CatalogRef $ref): int
    {
        return $ref->isCentral() ? $ref->id : $ref->id + self::OWN_OFFSET;
    }

    public static function ref(int $key): ?CatalogRef
    {
        return CategoryKey::ref($key);
    }

    /** Key (int / numeric string) or ref ("c:12" / "t:3") → ref; anything else → null. */
    public static function parse(mixed $value): ?CatalogRef
    {
        return CategoryKey::parse($value);
    }

    public static function keyOf(mixed $value): ?int
    {
        $ref = self::parse($value);

        return $ref === null ? null : self::of($ref);
    }

    public static function isOwnKey(int $key): bool
    {
        return $key >= self::OWN_OFFSET;
    }

    /** Typed pair (`central`|`tenant`, id) → key. */
    public static function fromPair(?string $type, int|string|null $id): ?int
    {
        if ($id === null || (int) $id < 1) {
            return null;
        }

        return $type === CatalogRef::CENTRAL ? (int) $id : ((int) $id) + self::OWN_OFFSET;
    }

    /**
     * SQL: key of a typed pair stored in two columns, e.g. sqlPairKey('f.`product_type`', 'f.`catalog_product_id`').
     * NULL type or id → NULL.
     */
    public static function sqlPairKey(string $typeColumn, string $idColumn): string
    {
        return "CASE WHEN {$idColumn} IS NULL THEN NULL WHEN {$typeColumn} = 'central' THEN {$idColumn} ELSE {$idColumn} + " . self::OWN_OFFSET . ' END';
    }

    /**
     * SQL: key of a TENANT `products` / `product_variants` row (alias given): a row linked to central keeps the central
     * id (a synced copy), an own row gets id + OWN_OFFSET. Works for legacy copies AND own rows, so a join from an old
     * tenant-id column to the merged key needs no merged table.
     */
    public static function sqlTenantRowKey(string $alias, string $centralLinkColumn = 'central_product_id'): string
    {
        return "COALESCE({$alias}.`{$centralLinkColumn}`, {$alias}.`id` + " . self::OWN_OFFSET . ')';
    }
}
