<?php

namespace App\Support\Catalog;

use LogicException;

/**
 * Thrown when code tries to change a PLATFORM-owned field of a merged catalog row (dedup plan phase 5). Rendered as a clean
 * 422 / back-with-error instead of a 500 (bootstrap/app.php), so the panel never crashes on a read-only central field.
 */
class ReadOnlyCatalogRowException extends LogicException
{
    public function userMessage(): string
    {
        return __('This field is managed by the platform catalog and cannot be edited here.');
    }
}
