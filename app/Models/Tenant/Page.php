<?php

namespace App\Models\Tenant;

use App\Models\Tenant\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Tenant\Concerns\OverlaysCentralConfig;
use App\Repositories\Tenant\Catalog\ConfigCatalog;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use HasFactory;
    use HasTranslations {
        loadedTranslations as protected traitLoadedTranslations;
    }
    use OverlaysCentralConfig;

    protected array $translated = ['title', 'body'];

    protected $fillable = [
        'slug',
        'active',
    ];

    protected static function configCatalogSection(): string
    {
        return 'pages';
    }

    /** @var array<string, \Illuminate\Database\Eloquent\Collection<int, Language>> tenant languages per tenant + version */
    private static array $languagesMemo = [];

    private static ?\WeakMap $mergedMemo = null;

    public static function flushCentralTranslationMemo(): void
    {
        self::$languagesMemo = [];
        self::$mergedMemo = null;
    }

    /**
     * Dedup plan phase 6: a `source = central` page shows central's title / body live (language matched by CODE) while
     * `catalog_source.pages` is `central`, for every locale the platform manages (ConfigCatalog::pageLockedLocales); the
     * vendor's own texts stay for the other locales (the Phase 4 category rule). Own pages / sync mode: unchanged.
     *
     * @param  iterable<int, Translation>  $tenantRows
     * @return \Illuminate\Database\Eloquent\Collection<int, Translation>|null  null = not applicable
     */
    private function mergedTranslations(iterable $tenantRows): ?\Illuminate\Database\Eloquent\Collection
    {
        if (($this->attributes['source'] ?? null) !== 'central' || ! isset($this->attributes['slug']) || ! ConfigCatalog::enabled('pages')) {
            return null;
        }

        $central = ConfigCatalog::pageTranslations((string) $this->attributes['slug']);
        $locked = array_flip(ConfigCatalog::pageLockedLocales((string) $this->attributes['slug'], 'central'));
        $memoKey = (string) tenant()?->getTenantKey() . '|' . \App\Support\CacheVersion::get('Language') . '|' . \App\Support\CacheVersion::get(ConfigCatalog::CACHE_TAG);
        $languages = self::$languagesMemo[$memoKey] ??= Language::query()->get();
        $byId = $languages->keyBy('id');
        $byCode = $languages->keyBy('code');

        $out = new \Illuminate\Database\Eloquent\Collection();
        foreach ($tenantRows as $row) {
            $code = $byId->get($row->language_id)?->code;
            if ($code !== null && ! isset($locked[$code])) {
                $out->push($row);
            }
        }
        foreach ($central as $field => $values) {
            foreach ($values as $code => $value) {
                if (! $language = $byCode->get($code)) {
                    continue;
                }
                $translation = (new Translation())->forceFill([
                    'language_id' => $language->id, 'translatable_type' => $this->getMorphClass(), 'translatable_id' => $this->getKey(),
                    'field' => $field, 'value' => $value,
                ]);
                $translation->exists = false;
                $translation->setRelation('language', $language);
                $out->push($translation);
            }
        }

        return $out;
    }

    protected function loadedTranslations()
    {
        $rows = $this->traitLoadedTranslations();
        if ($this->relationLoaded('translations')) {
            return $rows; // already merged by setRelation()
        }

        self::$mergedMemo ??= new \WeakMap();
        if (! isset(self::$mergedMemo[$rows])) {
            self::$mergedMemo[$rows] = $this->mergedTranslations($rows) ?? $rows;
        }

        return self::$mergedMemo[$rows];
    }

    public function setRelation($relation, $value)
    {
        if ($relation === 'translations' && is_iterable($value) && ($merged = $this->mergedTranslations($value)) !== null) {
            $value = $merged;
        }

        return parent::setRelation($relation, $value);
    }

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }
}
