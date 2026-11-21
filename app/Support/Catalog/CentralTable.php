<?php

namespace App\Support\Catalog;

use InvalidArgumentException;
use RuntimeException;

/**
 * Fully-qualified name of a CENTRAL table for use on the TENANT connection (dedup plan §3.2, invariants 2 + 5):
 * `CentralTable::name('products')` => "`dokan_v2`.`products`" (or "`dokan_v2_testing`.`products`" in tests).
 *
 * The schema name always comes from the central connection's config, never a literal. Read-only use only
 * (invariant 1): tenant code never writes to the central schema; see ReadOnlyCentralModel and the guard test.
 */
final class CentralTable
{
    /** Quoted "`db`.`table`" for raw SQL (joins, sub-selects). */
    public static function name(string $table): string
    {
        return '`' . self::database() . '`.`' . self::assertIdentifier($table) . '`';
    }

    /** Unquoted "db.table" for Eloquent `$table` / query builder `from()` (the grammar quotes each segment). */
    public static function qualified(string $table): string
    {
        return self::database() . '.' . self::assertIdentifier($table);
    }

    /** Central schema (database) name, from config('tenancy.database.central_connection'). */
    public static function database(): string
    {
        $connection = (string) config('tenancy.database.central_connection');
        $database = (string) config("database.connections.{$connection}.database");

        if ($database === '') {
            throw new RuntimeException("Central database name is not configured (connection [{$connection}]).");
        }

        return self::assertIdentifier($database);
    }

    private static function assertIdentifier(string $name): string
    {
        if (! preg_match('/^[A-Za-z0-9_$]+$/', $name)) {
            throw new InvalidArgumentException("Invalid SQL identifier [{$name}].");
        }

        return $name;
    }
}
