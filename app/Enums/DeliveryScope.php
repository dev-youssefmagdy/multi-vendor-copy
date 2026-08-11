<?php

namespace App\Enums;

enum DeliveryScope: string
{
    case AllZones = 'all_zones';
    case SelectedZones = 'selected_zones';
    case Digital = 'digital';

    public function label(): string
    {
        return match ($this) {
            self::AllZones => 'All zones',
            self::SelectedZones => 'Selected zones',
            self::Digital => 'Digital only',
        };
    }
}
