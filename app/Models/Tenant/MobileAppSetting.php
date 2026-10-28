<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

/** Key/value (json) store for the tenant's mobile app configuration. */
class MobileAppSetting extends Model
{
    protected $fillable = ['key', 'value'];

    protected function casts(): array
    {
        return ['value' => 'array'];
    }

    /** @return array<string, mixed> */
    public static function allKeyed(): array
    {
        return static::query()->get()->mapWithKeys(fn ($r) => [$r->key => $r->value])->all();
    }
}
