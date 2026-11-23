<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Tenant\Concerns\OverlaysCentralConfig;
use App\Repositories\Tenant\Catalog\ConfigCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailTemplate extends Model
{
    use HasFactory;
    use OverlaysCentralConfig;

    protected $fillable = [
        'central_email_template_id',
        'name',
        'action',
        'subject',
        'body',
        'is_active',
    ];

    protected static function configCatalogSection(): string
    {
        return 'email-templates';
    }

    /** Dedup plan phase 6: a vendor edit of subject / body / is_active wins over central from now on. */
    protected static function booted(): void
    {
        static::saving(function (EmailTemplate $template): void {
            if ($template->exists && $template->isDirty(['subject', 'body', 'is_active']) && ConfigCatalog::enabled('email-templates')
                && (int) $template->getRawOriginal('central_overridden') === 0) {
                // First vendor edit: freeze the columns the vendor did not touch at what they SAW (central's live
                // value), otherwise the copy's stale text would reappear for them.
                $frozen = [];
                foreach (['subject', 'body', 'is_active'] as $column) {
                    if (! $template->isDirty($column) && array_key_exists($column, $template->getAttributes())) {
                        $frozen[$column] = $template->getAttributes()[$column];
                    }
                }
                $frozen && $template->getConnection()->table($template->getTable())->where($template->getKeyName(), $template->getKey())->update($frozen);
                $template->setAttribute('central_overridden', 1);
            }
        });
    }

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
