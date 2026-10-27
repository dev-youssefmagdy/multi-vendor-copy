<?php

namespace App\Models\Pivots;

use Illuminate\Database\Eloquent\Model;

/**
 * Generic row of a `<model>_country` pivot table. The table and connection are
 * set per owner by App\Models\Concerns\HasCountries::countryRows().
 */
class CountryPivotRow extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = null;

    protected $guarded = [];
}
