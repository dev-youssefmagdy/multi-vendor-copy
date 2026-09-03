<?php

namespace App\Models;

use App\Enums\ActivationStatus;
use App\Enums\EmailTemplateType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'action',
        'subject',
        'body',
        'type',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'type' => EmailTemplateType::class,
            'status' => ActivationStatus::class,
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
