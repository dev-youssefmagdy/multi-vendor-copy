<?php

namespace App\Enums;

enum ReturnReason: string
{
    case Defective = 'defective';
    case WrongItem = 'wrong_item';
    case NotAsDescribed = 'not_as_described';
    case ChangedMind = 'changed_mind';
    case SizeOrFit = 'size_or_fit';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Defective => 'Defective / Damaged',
            self::WrongItem => 'Wrong Item Received',
            self::NotAsDescribed => 'Not as Described',
            self::ChangedMind => 'Changed My Mind',
            self::SizeOrFit => 'Size or Fit Issue',
            self::Other => 'Other',
        };
    }
}
