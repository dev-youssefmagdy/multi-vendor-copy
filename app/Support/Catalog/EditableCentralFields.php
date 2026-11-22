<?php

namespace App\Support\Catalog;

/**
 * Dedup plan §4.2a / D8: which fields of a central entity a vendor may ask to change (edit request → approved
 * field override). Only these (field, locale) pairs ever land in `tenant_field_overrides`. Today: product name and
 * description (translations). Extending the list is a product-owner decision (D8); prices, profit, active, featured
 * and sort order are NOT requests, vendors set them directly in the override tables (§4.1).
 */
final class EditableCentralFields
{
    public const CENTRAL_PRODUCT = 'central_product';

    /** entity type => [field => translatable?] */
    private const FIELDS = [
        self::CENTRAL_PRODUCT => ['name' => true, 'description' => true],
    ];

    /** @return list<string> */
    public static function for(string $entityType): array
    {
        return array_keys(self::FIELDS[$entityType] ?? []);
    }

    public static function allows(string $entityType, string $field): bool
    {
        return isset(self::FIELDS[$entityType][$field]);
    }

    public static function translatable(string $entityType, string $field): bool
    {
        return self::FIELDS[$entityType][$field] ?? false;
    }
}
