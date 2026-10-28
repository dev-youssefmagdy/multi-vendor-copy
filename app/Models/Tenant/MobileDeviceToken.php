<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

/** Push (FCM) device token. Stored only; nothing is sent from here. */
class MobileDeviceToken extends Model
{
    protected $fillable = ['customer_id', 'token', 'platform', 'app_version'];
}
