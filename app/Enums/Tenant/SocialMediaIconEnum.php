<?php

namespace App\Enums\Tenant;

enum SocialMediaIconEnum
{
    case facebook;
    case twitter;
    case instagram;
    case linkedin;
    case youtube;
    case whatsapp;

    function with($library): string
    {
        return match ($library) {
            'font-awesome' => $this->fontAwesome($this),
        };
    }

    function fontAwesome($for): string
    {
        return match ($this) {
            self::facebook => 'fab fa-facebook-f',
            self::twitter => 'fab fa-twitter',
            self::instagram => 'fab fa-instagram',
            self::linkedin => 'fab fa-linkedin-in',
            self::youtube => 'fab fa-youtube',
            self::whatsapp => 'fab fa-whatsapp',
        };
    }
}
