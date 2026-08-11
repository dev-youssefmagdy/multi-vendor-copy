<?php

namespace App\Enums;

enum FileType: string
{
    case Image = 'image';
    case Document = 'document';
    case Video = 'video';
    case Other = 'other';
}
