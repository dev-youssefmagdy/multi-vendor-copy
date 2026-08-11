<?php

namespace App\Enums;

enum AppSettingType: string
{
    case String = 'string';
    case Boolean = 'boolean';
    case Integer = 'integer';
    case Json = 'json';
    case Secret = 'secret';
}
