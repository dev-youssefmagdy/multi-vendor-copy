<?php

namespace App\Enums;

enum EmailTemplateType: string
{
    case Admin = 'admin';
    case Tenant = 'tenant';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Tenant => 'Tenant',
        };
    }
}
