<?php

namespace App\Enums;

enum ProductRequestStatus: string
{
    case Pending     = 'pending';
    case Reviewing   = 'reviewing';
    case InProduction = 'in_production';
    case Completed   = 'completed';
    case Rejected    = 'rejected';
    // PANELS-UPDATES-PRODUCT-REQUESTS: finished requests (completed / rejected) can be archived to keep the list clean.
    case Archived    = 'archived';

    public function label(): string
    {
        return match($this) {
            self::Pending      => 'Pending',
            self::Reviewing    => 'Under Review',
            self::InProduction => 'In Production',
            self::Completed    => 'Completed',
            self::Rejected     => 'Rejected',
            self::Archived     => 'Archived',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Pending      => 'badge-amber',
            self::Reviewing    => 'badge-cyan',
            self::InProduction => 'badge-violet',
            self::Completed    => 'badge-green',
            self::Rejected     => 'badge-red',
            self::Archived     => 'badge-secondary',
        };
    }

    public function stepNumber(): int
    {
        return match($this) {
            self::Pending      => 1,
            self::Reviewing    => 2,
            self::InProduction => 3,
            self::Completed    => 4,
            self::Rejected     => 0,
            self::Archived     => 4,
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn(self $s) => [$s->value => $s->label()])
            ->all();
    }

    /** @return array<string, string> admin-only status transitions */
    public static function adminOptions(): array
    {
        return self::options();
    }
}
