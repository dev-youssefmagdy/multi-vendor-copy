<?php

namespace App\Support\Catalog;

use InvalidArgumentException;
use Stringable;

/**
 * Identity of a merged catalog entity (dedup plan §3.1): which side owns it + its id on that side.
 * String form for UI / routes / cart keys: "c:123" (central) or "t:45" (tenant).
 */
final class CatalogRef implements Stringable
{
    public const CENTRAL = 'central';
    public const TENANT = 'tenant';

    private const PREFIX = [self::CENTRAL => 'c', self::TENANT => 't'];

    private function __construct(public readonly string $source, public readonly int $id)
    {
    }

    public static function make(string $source, int|string $id): self
    {
        $source = strtolower(trim($source));
        if (! isset(self::PREFIX[$source])) {
            throw new InvalidArgumentException("Invalid catalog source [{$source}]; expected central or tenant.");
        }

        return new self($source, self::assertId($id));
    }

    public static function central(int|string $id): self
    {
        return self::make(self::CENTRAL, $id);
    }

    public static function tenant(int|string $id): self
    {
        return self::make(self::TENANT, $id);
    }

    /** Parses "c:123" / "t:45". Anything else (bare ids, other prefixes, non-positive ids) throws. */
    public static function parse(string $value): self
    {
        if (! preg_match('/^([ct]):([1-9][0-9]*)$/', trim($value), $m)) {
            throw new InvalidArgumentException("Invalid catalog reference [{$value}]; expected c:<id> or t:<id>.");
        }

        return self::make(array_search($m[1], self::PREFIX, true), $m[2]);
    }

    /** parse() that returns null instead of throwing (for untrusted request input). */
    public static function tryParse(?string $value): ?self
    {
        try {
            return $value === null ? null : self::parse($value);
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    public function isCentral(): bool
    {
        return $this->source === self::CENTRAL;
    }

    public function isTenant(): bool
    {
        return $this->source === self::TENANT;
    }

    public function equals(self $other): bool
    {
        return $this->source === $other->source && $this->id === $other->id;
    }

    public function __toString(): string
    {
        return self::PREFIX[$this->source] . ':' . $this->id;
    }

    private static function assertId(int|string $id): int
    {
        if (is_string($id)) {
            if (! preg_match('/^[1-9][0-9]*$/', $id) || strlen($id) > 18) {
                throw new InvalidArgumentException("Invalid catalog id [{$id}].");
            }
            $id = (int) $id;
        }

        if ($id < 1) {
            throw new InvalidArgumentException("Invalid catalog id [{$id}].");
        }

        return $id;
    }
}
