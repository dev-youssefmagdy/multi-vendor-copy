<?php

namespace App\Enums;

enum ContentStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Published = 'published';
    case Archived = 'archived';

    public function label(): string
    {
        return str($this->value)->headline()->toString();
    }
}
