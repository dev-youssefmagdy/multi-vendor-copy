<?php

namespace App\Support\Catalog;

use Illuminate\Database\Connection;

/**
 * Runtime check for dedup plan invariant 1 ("tenant code never writes to the central schema"): inspects every
 * statement a (tenant) connection is about to run and throws CentralWriteAttempted when its write target is a
 * central table (`<central db>`.`table`). Reads of central (SELECT, or INSERT ... SELECT FROM central into a
 * tenant table) are allowed.
 *
 * Not installed in production code yet (phase 0 = no behaviour change); used by the guard test. Phase 2+ may
 * install it on the tenant connection in non-production environments.
 */
final class CentralWriteGuard
{
    public static function install(Connection $connection): void
    {
        $connection->beforeExecuting(function (string $query): void {
            if (self::writesToCentral($query)) {
                throw CentralWriteAttempted::on(CentralTable::database(), strtok(trim($query), " \n\t") ?: 'write');
            }
        });
    }

    public static function writesToCentral(string $sql): bool
    {
        $db = preg_quote(CentralTable::database(), '/');
        $central = '`?' . $db . '`?\s*\.\s*`?[A-Za-z0-9_$]+`?';
        $sql = trim($sql);

        // Statement whose target table is central.
        $target = '/^(?:insert(?:\s+ignore)?\s+into|replace\s+into|update(?:\s+ignore)?|delete(?:\s+ignore)?\s+from'
            . '|truncate(?:\s+table)?|alter\s+table|drop\s+table(?:\s+if\s+exists)?|rename\s+table|create\s+table(?:\s+if\s+not\s+exists)?)\s+'
            . $central . '/i';
        if (preg_match($target, $sql)) {
            return true;
        }

        // Multi-table UPDATE assigning a central column: "... SET `db`.`t`.`col` = ...".
        if (preg_match('/^update\s/i', $sql) && preg_match('/\sset\s(.+)$/is', $sql, $m)) {
            $set = preg_split('/\swhere\s/i', $m[1])[0];
            if (preg_match('/(^|,)\s*' . $central . '\s*\.\s*`?[A-Za-z0-9_$]+`?\s*=/i', $set)) {
                return true;
            }
        }

        // Multi-table DELETE naming a central table before FROM: "DELETE `db`.`t` FROM ...".
        return (bool) preg_match('/^delete\s+(?!from\s)[^;]*?' . $central . '[^;]*?\sfrom\s/i', $sql);
    }
}
