<?php

namespace App\Enums;

enum ReturnMediaType: string
{
    case Photo = 'photo';
    case Video = 'video';

    public function label(): string
    {
        return match ($this) {
            self::Photo => 'Photo',
            self::Video => 'Video',
        };
    }
}
