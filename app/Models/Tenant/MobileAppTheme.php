<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

/** The tenant's chosen mobile app theme + home variant (independent of the web theme). */
class MobileAppTheme extends Model
{
    protected $table = 'mobile_app_theme';

    protected $fillable = ['theme_slug', 'variant_key'];
}
