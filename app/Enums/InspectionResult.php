<?php

namespace App\Enums;

enum InspectionResult: string
{
    case Passed = 'passed';
    case Partial = 'partial';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Passed => __('Passed'),
            self::Partial => __('Partially accepted'),
            self::Failed => __('Failed'),
        };
    }

    /** Customer-friendly wording shown on the storefront return detail page. */
    public function customerLabel(): string
    {
        return match ($this) {
            self::Passed => __('Your item passed inspection'),
            self::Partial => __('Your item was partially accepted after inspection'),
            self::Failed => __('Your item did not pass inspection'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Passed => 'green',
            self::Partial => 'amber',
            self::Failed => 'red',
        };
    }

    /** The returned goods may be put back into stock. */
    public function allowsRestock(): bool
    {
        return $this !== self::Failed;
    }
}
