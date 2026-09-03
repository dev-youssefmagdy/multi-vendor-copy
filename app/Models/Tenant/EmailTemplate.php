<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'central_email_template_id',
        'name',
        'action',
        'subject',
        'body',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'central_email_template_id' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function translations(): HasMany
    {
        return $this->hasMany(EmailTemplateTranslation::class);
    }

    public function translatedSubject(string $locale): string
    {
        return $this->translations->firstWhere('locale', $locale)?->subject ?? $this->subject;
    }

    public function translatedBody(string $locale): ?string
    {
        $translation = $this->translations->firstWhere('locale', $locale);
        return $translation ? $translation->body : $this->body;
    }
}
