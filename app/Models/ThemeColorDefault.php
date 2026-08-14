<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Admin-set global default color value for a theme variable.
 * Layered between the theme's hardcoded default and a tenant's override.
 */
class ThemeColorDefault extends Model
{
    use HasFactory;

    protected $fillable = [
        'theme_slug',
        'variable_key',
        'value',
    ];
}
