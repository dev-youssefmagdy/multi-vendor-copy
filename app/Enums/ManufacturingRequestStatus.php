<?php

namespace App\Enums;

enum ManufacturingRequestStatus: string
{
    case Pending = 'pending';
    case UnderReview = 'under_review';
    case InProduction = 'in_production';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::UnderReview => 'Under Review',
            self::InProduction => 'In Production',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
            self::Rejected => 'Rejected',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending => 'badge badge-amber',
            self::UnderReview => 'badge badge-blue',
            self::InProduction => 'badge badge-cyan',
            self::Completed => 'badge badge-green',
            self::Cancelled => 'badge badge-red',
            self::Rejected => 'badge badge-red',
        };
    }
}
