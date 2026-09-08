<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class Template extends Model
{
    use HasFactory, CentralConnection;

    protected $fillable = [
        'name',
        'slug',
        'version',
        'author',
        'is_active',
        'is_default',
        'preview_file_id',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    public function previewFile(): BelongsTo
    {
        return $this->belongsTo(File::class, 'preview_file_id');
    }

    public function countries(): BelongsToMany
    {
        return $this->belongsToMany(Country::class, 'template_country')
            ->withTimestamps()
            ->orderBy('countries.name');
    }

    public function parts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TemplatePart::class)->orderBy('type')->orderByDesc('is_default')->orderBy('name');
    }
}
