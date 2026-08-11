<?php

namespace App\Models;

use App\Enums\SubscriberStatus;
use Illuminate\Database\Eloquent\Model;

class NewsletterSubscriber extends Model
{
    protected $fillable = [
        'email',
        'name',
        'status',
        'ip_address',
        'source',
        'subscribed_at',
        'unsubscribed_at',
    ];

    protected $casts = [
        'status'           => SubscriberStatus::class,
        'subscribed_at'    => 'datetime',
        'unsubscribed_at'  => 'datetime',
    ];
}

