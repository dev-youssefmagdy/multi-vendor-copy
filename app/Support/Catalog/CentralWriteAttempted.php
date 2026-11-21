<?php

namespace App\Support\Catalog;

use LogicException;

/** Thrown when tenant code tries to write through a read-only central model (dedup plan invariant 1). */
class CentralWriteAttempted extends LogicException
{
    public static function on(string $target, string $operation): self
    {
        return new self("Tenant code must not write to the central schema: {$operation} on [{$target}] is not allowed.");
    }
}
