<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class DnsRecord extends Model
{
    use CentralConnection;

    protected $fillable = [
        'type',
        'name',
        'value',
        'ttl',
        'priority',
        'description',
        'is_required',
    ];

    protected function casts(): array
    {
        return [
            'ttl' => 'integer',
            'priority' => 'integer',
            'is_required' => 'boolean',
        ];
    }

    public static function types(): array
    {
        return ['A', 'AAAA', 'CNAME', 'MX', 'TXT', 'NS', 'SRV'];
    }
}
