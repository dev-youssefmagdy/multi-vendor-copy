<?php

namespace App\Enums\Tenant;

enum SettingType: string
{
    case File = 'file';
    case Image = 'image';
    case Boolean = 'bool';
    case Select = 'select';
    case Color = 'color';
    case String = 'string';
    case TextEditor = 'text_editor';
    case Number = 'number';
    case Phone = 'phone';
    case Email = 'email';
    case Password = 'password';
    case Json = 'json';

    public function label(): string
    {
        return str($this->value)->headline()->replace('_', ' ')->toString();
    }
}
