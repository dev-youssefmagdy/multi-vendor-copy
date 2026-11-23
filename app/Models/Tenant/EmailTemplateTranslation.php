<?php

namespace App\Models\Tenant;

use App\Repositories\Tenant\Catalog\ConfigCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailTemplateTranslation extends Model
{
    /**
     * Dedup plan phase 6: an identity translation row shows central's locale text live until the vendor edits it
     * (`central_overridden`); the owner template's central id is resolved once per request.
     *
     * @var array<string, array<int, int>>
     */
    private static array $centralIds = [];

    public static function flushCentralIds(): void
    {
        self::$centralIds = [];
    }

    protected static function booted(): void
    {
        static::saving(function (EmailTemplateTranslation $translation): void {
            // A row the vendor creates through the model (panel save) is the vendor's text; identity rows are inserted
            // through the query builder (ConfigIdentity) and stay 0 = follow central.
            if (! $translation->exists && ConfigCatalog::enabled('email-templates') && ! $translation->isDirty('central_overridden')) {
                $translation->setAttribute('central_overridden', 1);

                return;
            }
            if ($translation->exists && $translation->isDirty(['subject', 'body']) && ConfigCatalog::enabled('email-templates')
                && (int) $translation->getRawOriginal('central_overridden') === 0) {
                $frozen = [];
                foreach (['subject', 'body'] as $column) {
                    if (! $translation->isDirty($column) && array_key_exists($column, $translation->getAttributes())) {
                        $frozen[$column] = $translation->getAttributes()[$column];
                    }
                }
                $frozen && $translation->getConnection()->table($translation->getTable())->where($translation->getKeyName(), $translation->getKey())->update($frozen);
                $translation->setAttribute('central_overridden', 1);
            }
        });
    }

    public function newFromBuilder($attributes = [], $connection = null)
    {
        $model = parent::newFromBuilder($attributes, $connection);
        $raw = $model->getAttributes();
        if (array_key_exists('central_overridden', $raw) && (int) $raw['central_overridden'] === 0 && ConfigCatalog::enabled('email-templates')) {
            $tenantKey = (string) tenant()?->getTenantKey();
            self::$centralIds[$tenantKey] ??= DB::connection('tenant')->table('email_templates')->whereNotNull('central_email_template_id')->pluck('central_email_template_id', 'id')->all();
            $centralId = self::$centralIds[$tenantKey][(int) ($raw['email_template_id'] ?? 0)] ?? null;
            $central = $centralId === null ? null : ConfigCatalog::emailTranslation((int) $centralId, (string) ($raw['locale'] ?? ''));
            if ($central !== null) {
                foreach (['subject', 'body'] as $column) {
                    if (array_key_exists($column, $raw)) {
                        $raw[$column] = $central[$column];
                    }
                }
                $model->setRawAttributes($raw, true);
            }
        }

        return $model;
    }

    protected $fillable = [
        'email_template_id',
        'locale',
        'subject',
        'body',
    ];

    public function emailTemplate(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class);
    }
}
