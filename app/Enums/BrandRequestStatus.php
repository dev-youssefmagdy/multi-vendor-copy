<?php

namespace App\Enums;

enum BrandRequestStatus: string
{
    case Pending = 'pending';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::UnderReview => 'Under Review',
            self::Approved => 'Approved',
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
            self::Approved => 'badge badge-cyan',
            self::Completed => 'badge badge-green',
            self::Cancelled => 'badge badge-red',
            self::Rejected => 'badge badge-red',
        };
    }
}
