<?php

namespace App\Enums;

enum TenantChangeRequestType: string
{
    case Countries = 'countries';
    case Categories = 'categories';

    public function label(): string
    {
        return match ($this) {
            self::Countries => 'Target Countries',
            self::Categories => 'Categories',
        };
    }
}
