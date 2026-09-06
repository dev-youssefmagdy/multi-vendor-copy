<?php

namespace App\Concerns;

trait SanitizesPhoneNumber
{
    protected function sanitizePhone(?string $phone): ?string
    {
        $clean = trim((string) preg_replace('/[^+\d\s\-()]+/', '', (string) $phone));

        return $clean !== '' ? $clean : null;
    }
}
