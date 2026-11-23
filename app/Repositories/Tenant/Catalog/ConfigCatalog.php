<?php

namespace App\Repositories\Tenant\Catalog;

use App\Enums\PaymentGatewayType;
use App\Models\AppSetting as CentralAppSetting;
use App\Models\PaymentGateway as CentralPaymentGateway;
use App\Models\Tenant;
use App\Support\CacheVersion;
use App\Support\Catalog\CatalogRefColumns;
use App\Support\Catalog\CatalogSource;
use App\Support\Catalog\CentralTable;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Dedup plan phase 6, config entities (languages, currencies, settings, payment gateways, themes, email templates,
 * pages). Once a tenant's `catalog_source.<section>` is `central`:
 *
 *  - the tenant row stays as an IDENTITY row (local FKs: translations, theme children, orders.payment_gateway_id,
 *    theme_country) and keeps every tenant-owned column (is_active / is_default / use_own / required_values / mode /
 *    theme choice / theme_country.is_enabled / vendor text with a `central_overridden` marker);
 *  - every CENTRAL-owned column is read live from central at model hydration (trait OverlaysCentralConfig), so the
 *    ~200 existing readers keep working unchanged, and a central edit is visible on the next read (no fan-out sync);
 *  - a tenant row whose central row is gone (or no longer eligible: unpurchased language, wrong gateway / template
 *    type) is hidden by the same trait's global scope;
 *  - the sync of the section does not copy data any more: it only inserts MISSING identity rows (ensureIdentity()).
 *
 * Behaviour-neutral for `sync` tenants: enabled() is false, nothing here runs. Central payloads are cached per tenant
 * under the global `CentralConfig` CacheVersion tag (bumped by CentralConfigObserver on every central write).
 */
final class ConfigCatalog
{
    /** Global CacheVersion tag bumped on any central config write (invariant 8). */
    public const CACHE_TAG = 'CentralConfig';

    public const SECTIONS = ['languages', 'currencies', 'settings', 'payment-gateways', 'themes', 'email-templates', 'pages'];

    /** section => [tenant table, tenant link column] (settings / pages: matched by name / slug). */
    public const LINKS = [
        'languages' => ['languages', 'central_language_id'],
        'currencies' => ['currencies', 'central_currency_id'],
        'payment-gateways' => ['payment_gateways', 'central_payment_gateway_id'],
        'themes' => ['themes', 'central_theme_id'],
        'email-templates' => ['email_templates', 'central_email_template_id'],
        'settings' => ['settings', 'name'],
        'pages' => ['pages', 'slug'],
    ];

    /** @var array<string, array<int|string, array<string, mixed>>> */
    private static array $memo = [];

    /** @var array<string, true> */
    private static array $warned = [];

    /** @var array<string, bool> */
    private static array $enabledMemo = [];

    public static function flushMemo(): void
    {
        self::$memo = [];
        self::$warned = [];
        self::$enabledMemo = [];
        \App\Models\Tenant\EmailTemplateTranslation::flushCentralIds();
        \App\Models\Tenant\Page::flushCentralTranslationMemo();
    }

    public static function enabled(string $section, ?Tenant $tenant = null): bool
    {
        $tenant ??= tenant();
        if (! $tenant instanceof Tenant || ! in_array($section, self::SECTIONS, true)) {
            return false;
        }

        // Hot path (every hydrated config row): memo per tenant + flag value, so a flipped flag is seen at once.
        $raw = $tenant->getAttribute(CatalogSource::ATTRIBUTE);
        $memoKey = $tenant->getTenantKey() . '|' . $section . '|' . (is_string($raw) ? $raw : json_encode($raw));
        if (isset(self::$enabledMemo[$memoKey])) {
            return self::$enabledMemo[$memoKey];
        }

        if (! CatalogSource::isCentral($tenant, $section)) {
            return self::$enabledMemo[$memoKey] = false;
        }

        // central: memoise only a "ready" answer, so a `tenants:migrate` is picked up by long-running workers.
        $ready = self::resolveEnabled($tenant, $section);
        if ($ready) {
            self::$enabledMemo[$memoKey] = true;
        }

        return $ready;
    }

    private static function resolveEnabled(Tenant $tenant, string $section): bool
    {
        if (self::schemaReady($section)) {
            return true;
        }

        if (! isset(self::$warned[$section])) {
            self::$warned[$section] = true;
            Log::warning("catalog_source.{$section} is central but the tenant DB lacks the phase-6 config schema; reading synced copies.", [
                'tenant' => $tenant->getTenantKey(),
            ]);
        }

        return false;
    }

    /** True when at least one config section is read from central (storefront cache keys carry the central version). */
    public static function anyEnabled(?Tenant $tenant = null): bool
    {
        foreach (self::SECTIONS as $section) {
            if (self::enabled($section, $tenant)) {
                return true;
            }
        }

        return false;
    }

    public static function cacheSuffix(): string
    {
        return self::anyEnabled() ? '.cc' . CacheVersion::get(self::CACHE_TAG) : '';
    }

    public static function schemaReady(string $section, ?ConnectionInterface $db = null): bool
    {
        $db ??= DB::connection('tenant');

        return match ($section) {
            'settings' => CatalogRefColumns::has($db, 'settings', 'central_overridden'),
            'email-templates' => CatalogRefColumns::has($db, 'email_templates', 'central_overridden')
                && CatalogRefColumns::has($db, 'email_template_translations', 'central_overridden'),
            'pages' => CatalogRefColumns::has($db, 'pages', 'source'),
            default => true,
        };
    }

    // ---------------------------------------------------------------------------------------------------------
    // Central payloads
    // ---------------------------------------------------------------------------------------------------------

    /**
     * Central-owned values of every VISIBLE central row, keyed by central id (settings: key, pages: slug).
     * An id missing from the map = hidden for this tenant.
     *
     * @return array<int|string, array<string, mixed>>
     */
    public static function map(string $section): array
    {
        $tenantKey = (string) (tenant()?->getTenantKey() ?? 'none');
        $memoKey = $tenantKey . '|' . $section . '|' . CacheVersion::get(self::CACHE_TAG);

        return self::$memo[$memoKey] ??= CacheVersion::store()->remember(
            'config-catalog:' . md5($memoKey),
            (int) config('cache.storefront.default_ttl', 3600),
            fn () => self::load($section, $tenantKey),
        );
    }

    /** Uncached central payload (identity inserts, dedup verify): never trusts the memo / cache. */
    public static function fresh(string $section): array
    {
        return self::load($section, (string) (tenant()?->getTenantKey() ?? 'none'));
    }

    /** @return array<int|string, array<string, mixed>> */
    private static function load(string $section, string $tenantKey): array
    {
        $db = DB::connection('tenant');

        return match ($section) {
            'languages' => self::loadLanguages($db, $tenantKey),
            'currencies' => self::keyed($db->select('SELECT `id`, `code`, `name`, `sign` AS `symbol`, `conversion_rate` FROM ' . CentralTable::name('currencies') . ' c WHERE ' . self::live($db, 'currencies')), 'id'),
            'themes' => self::loadThemes($db),
            'payment-gateways' => self::loadGateways(),
            'email-templates' => self::loadEmailTemplates($db),
            'settings' => self::loadSettings(),
            'pages' => self::loadPages($db),
        };
    }

    private static function loadThemes(ConnectionInterface $db): array
    {
        $out = self::keyed($db->select(
            'SELECT c.`id`, c.`name`, c.`slug`,
                    CASE WHEN EXISTS (SELECT 1 FROM ' . CentralTable::name('template_country') . ' tc WHERE tc.`template_id` = c.`id`) THEN 0 ELSE 1 END AS `is_universal`
               FROM ' . CentralTable::name('templates') . ' c WHERE ' . self::live($db, 'templates')
        ), 'id');
        // preview_path: the same value the themes sync stores (`previewFile->full_path`), read live.
        if ($out !== []) {
            foreach (\App\Models\Template::query()->with('previewFile')->whereIn('id', array_keys($out))->get(['id', 'preview_file_id']) as $template) {
                $out[(int) $template->id]['preview_path'] = $template->previewFile?->full_path;
            }
        }

        return $out;
    }

    /** Central static pages by slug: status + title / body per language CODE (one query for all pages). */
    private static function loadPages(ConnectionInterface $db): array
    {
        $out = [];
        foreach ($db->select('SELECT c.`id`, c.`slug`, c.`status` FROM ' . CentralTable::name('static_pages') . ' c WHERE ' . self::live($db, 'static_pages')) as $row) {
            $out[(string) $row->slug] = ['id' => (int) $row->id, 'status' => $row->status, 'translations' => []];
        }
        if ($out === []) {
            return $out;
        }

        $slugById = array_combine(array_column($out, 'id'), array_keys($out));
        $rows = $db->select(
            'SELECT t.`translatable_id`, l.`code`, t.`field`, t.`value`
               FROM ' . CentralTable::name('translations') . ' t
               JOIN ' . CentralTable::name('languages') . ' l ON l.`id` = t.`language_id`
              WHERE t.`translatable_type` = ? AND t.`translatable_id` IN (' . implode(',', array_keys($slugById)) . ')',
            [(new \App\Models\StaticPage())->getMorphClass()],
        );
        foreach ($rows as $row) {
            if ($row->value === null || $row->value === '') {
                continue;
            }
            // central static pages call the body `content`; the tenant page calls it `body`.
            $field = $row->field === 'content' ? 'body' : $row->field;
            if (in_array($field, ['title', 'body'], true)) {
                $out[$slugById[(int) $row->translatable_id]]['translations'][$field][$row->code] = (string) $row->value;
            }
        }

        return $out;
    }

    private static function loadLanguages(ConnectionInterface $db, string $tenantKey): array
    {
        $rows = $db->select(
            'SELECT c.`id`, c.`code`, c.`name`, c.`native_name`, c.`direction`, c.`sort_order`, c.`is_default` AS `central_default`, c.`is_active` AS `central_active`
               FROM ' . CentralTable::name('languages') . ' c
              WHERE ' . self::live($db, 'languages') . '
                AND (c.`is_free` = 1 OR c.`id` IN (SELECT p.`central_language_id` FROM ' . CentralTable::name('tenant_language_purchases') . ' p WHERE p.`tenant_id` = ?))',
            [$tenantKey],
        );

        return self::keyed($rows, 'id');
    }

    private static function loadGateways(): array
    {
        $out = [];
        foreach (CentralPaymentGateway::query()->where('type', PaymentGatewayType::Orders->value)->get() as $gateway) {
            $active = $gateway->status === \App\Enums\ActivationStatus::Active;
            $out[(int) $gateway->id] = [
                'name' => $gateway->name,
                'code' => $gateway->code,
                'is_active' => $active ? 1 : 0,
                'hide' => $active ? 0 : 1,
                'required_keys' => json_encode(array_values(array_unique(array_keys((array) ($gateway->credentials ?? [])))), JSON_UNESCAPED_UNICODE),
            ];
        }

        return $out;
    }

    private static function loadEmailTemplates(ConnectionInterface $db): array
    {
        $rows = $db->select(
            'SELECT c.`id`, c.`name`, c.`action`, c.`subject`, c.`body`, c.`status` FROM ' . CentralTable::name('email_templates') . ' c
              WHERE c.`type` = ? AND ' . self::live($db, 'email_templates'),
            ['tenant'],
        );
        $out = [];
        foreach ($rows as $row) {
            $out[(int) $row->id] = [
                'name' => $row->name, 'action' => $row->action, 'subject' => $row->subject, 'body' => $row->body,
                'is_active' => $row->status === 'active' ? 1 : 0, 'translations' => [],
            ];
        }
        if ($out !== []) {
            $translations = $db->select('SELECT `email_template_id`, `locale`, `subject`, `body` FROM ' . CentralTable::name('email_template_translations') . ' WHERE `email_template_id` IN (' . implode(',', array_keys($out)) . ')');
            foreach ($translations as $t) {
                $out[(int) $t->email_template_id]['translations'][$t->locale] = ['subject' => $t->subject, 'body' => $t->body];
            }
        }

        return $out;
    }

    /**
     * Central keys a tenant never inherits live (phase 6 review):
     *  - `mail_*`: the vendor's SMTP rows are used all-or-nothing (TenantPanelService::saveMailSettings), central mail is
     *    the separate fallback; overlaying central values onto a vendor row would mix two mail accounts;
     *  - `type = secret` (openai_api_key, mail_password, ...): central credentials are never handed to a tenant;
     *  - `default_currency` / `default_language`: the tenant's own choice (mirrors `currencies/languages.is_default`).
     * Such tenant rows keep reading their own value (as `sync` does), and no identity row is created for them.
     */
    public const TENANT_OWNED_SETTING_KEYS = ['default_currency', 'default_language'];

    public static function inheritsSetting(string $key, mixed $type = null): bool
    {
        $type = $type instanceof \BackedEnum ? $type->value : (string) $type;

        return ! str_starts_with($key, 'mail_') && $type !== 'secret' && ! in_array($key, self::TENANT_OWNED_SETTING_KEYS, true);
    }

    private static function loadSettings(): array
    {
        $out = [];
        foreach (CentralAppSetting::query()->orderBy('key')->get(['key', 'value', 'type']) as $setting) {
            if (self::inheritsSetting((string) $setting->key, $setting->type)) {
                $out[$setting->key] = ['value' => self::settingValue($setting->value)];
            }
        }

        return $out;
    }

    /** Same normalisation the settings sync applies when it seeds a copy. */
    public static function settingValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private static function live(ConnectionInterface $db, string $centralTable): string
    {
        return CatalogRefColumns::has($db, $centralTable, 'deleted_at', CentralTable::database()) ? 'c.`deleted_at` IS NULL' : '1 = 1';
    }

    /** @return array<int|string, array<string, mixed>> */
    private static function keyed(array $rows, string $key): array
    {
        $out = [];
        foreach ($rows as $row) {
            $row = (array) $row;
            $id = $row[$key];
            unset($row[$key]);
            $out[$key === 'id' ? (int) $id : (string) $id] = $row;
        }

        return $out;
    }

    // ---------------------------------------------------------------------------------------------------------
    // Overlay (one row)
    // ---------------------------------------------------------------------------------------------------------

    /**
     * The central-owned columns a tenant row must show. `null` = the row has no central counterpart to overlay
     * (own row) or the model has no section; `[]` = nothing to change. Pure function of the row's own attributes, so
     * dedup verify can call it with a raw DB row.
     *
     * @param  array<string, mixed>  $row  raw tenant row attributes
     * @param  array<int|string, array<string, mixed>>|null  $map  central payload (default: the cached map; dedup passes fresh())
     * @return array<string, mixed>|null
     */
    public static function overlayValues(string $section, array $row, ?array $map = null): ?array
    {
        [$table, $link] = self::LINKS[$section];
        $key = $row[$link] ?? null;
        if ($key === null || $key === '') {
            return null;
        }
        if ($section === 'pages' && ($row['source'] ?? 'central') !== 'central') {
            return null; // the vendor's own page
        }

        $central = ($map ?? self::map($section))[$section === 'settings' || $section === 'pages' ? (string) $key : (int) $key] ?? null;
        if ($central === null) {
            return null; // hidden by the scope (or settings / pages key central does not have: tenant-only row)
        }

        return match ($section) {
            'currencies' => ['code' => strtoupper((string) $central['code']) ?: ($row['code'] ?? null), 'name' => $central['name'], 'symbol' => $central['symbol'], 'conversion_rate' => $central['conversion_rate']],
            'themes' => ['name' => $central['name'], 'slug' => $central['slug'], 'is_universal' => (int) $central['is_universal']]
                + (array_key_exists('preview_path', $central) ? ['preview_path' => $central['preview_path']] : []),
            'payment-gateways' => $central,
            'languages' => [
                'code' => $central['code'], 'name' => $central['name'], 'native_name' => $central['native_name'],
                'direction' => $central['direction'], 'sort_order' => $central['sort_order'],
                // Central may switch a language off platform-wide; it never re-enables a tenant-disabled one.
                'is_active' => ((int) ($row['is_active'] ?? 1) === 1 && (int) $central['central_active'] === 1) ? 1 : 0,
            ],
            'settings' => (int) ($row['central_overridden'] ?? 0) === 1 || ! array_key_exists('central_overridden', $row) ? [] : ['value' => $central['value']],
            'email-templates' => self::emailTemplateValues($row, $central),
            'pages' => ['active' => ((int) ($row['active'] ?? 1) === 1 && in_array($central['status'], ['active', 'published'], true)) ? 1 : 0],
            default => [],
        };
    }

    private static function emailTemplateValues(array $row, array $central): array
    {
        if (! array_key_exists('central_overridden', $row)) {
            return [];
        }
        $values = ['name' => $central['name'], 'action' => $central['action']];
        if ((int) $row['central_overridden'] === 0) {
            $values += ['subject' => $central['subject'], 'body' => $central['body'], 'is_active' => $central['is_active']];
        }

        return $values;
    }

    /** Central subject / body of one template locale (identity-row translations); null = none. */
    public static function emailTranslation(int $centralTemplateId, string $locale): ?array
    {
        return self::map('email-templates')[$centralTemplateId]['translations'][$locale] ?? null;
    }

    // ---------------------------------------------------------------------------------------------------------
    // Hiding + central-owned columns in WHERE / ORDER BY (global scope SQL)
    // ---------------------------------------------------------------------------------------------------------

    /**
     * SQL condition for "this row is visible": an own row (no link) or a row whose central counterpart is still visible.
     * $ref = the table name or alias the query uses. Returns [sql, bindings].
     *
     * @return array{0: string, 1: array<int, mixed>}
     */
    public static function visibleSql(string $section, ?string $ref = null): array
    {
        $db = DB::connection('tenant');
        [$table, $link] = self::LINKS[$section];
        $t = '`' . ($ref ?? $table) . '`';
        $col = "{$t}.`{$link}`";

        switch ($section) {
            case 'languages':
                $tenantKey = (string) tenant()?->getTenantKey();

                return ["({$col} IS NULL OR EXISTS (SELECT 1 FROM " . CentralTable::name('languages') . ' c WHERE c.`id` = ' . $col . ' AND ' . self::live($db, 'languages')
                    . ' AND (c.`is_free` = 1 OR c.`id` IN (SELECT p.`central_language_id` FROM ' . CentralTable::name('tenant_language_purchases') . ' p WHERE p.`tenant_id` = ?))))', [$tenantKey]];
            case 'currencies':
                return ["({$col} IS NULL OR EXISTS (SELECT 1 FROM " . CentralTable::name('currencies') . " c WHERE c.`id` = {$col} AND " . self::live($db, 'currencies') . '))', []];
            case 'themes':
                return ["({$col} IS NULL OR EXISTS (SELECT 1 FROM " . CentralTable::name('templates') . " c WHERE c.`id` = {$col} AND " . self::live($db, 'templates') . '))', []];
            case 'payment-gateways':
                return ["({$col} IS NULL OR EXISTS (SELECT 1 FROM " . CentralTable::name('payment_gateways') . " c WHERE c.`id` = {$col} AND c.`type` = ?))", [PaymentGatewayType::Orders->value]];
            case 'email-templates':
                return ["({$col} IS NULL OR EXISTS (SELECT 1 FROM " . CentralTable::name('email_templates') . " c WHERE c.`id` = {$col} AND c.`type` = 'tenant' AND " . self::live($db, 'email_templates') . '))', []];
            case 'pages':
                // `source = central` pages exist only while central has the slug; the vendor's own pages always show.
                return ["({$t}.`source` <> 'central' OR EXISTS (SELECT 1 FROM " . CentralTable::name('static_pages') . " c WHERE c.`slug` = {$t}.`slug` AND " . self::live($db, 'static_pages') . '))', []];
            default:
                return ['1 = 1', []];
        }
    }

    /**
     * SQL expression of a CENTRAL-owned column as the overlay shows it, for WHERE / ORDER BY (phase 6 review: the identity
     * copy is no longer refreshed in `central` mode, so `where('is_active', true)` / `where('slug', ...)` /
     * `orderBy('sort_order')` must evaluate on central's live value, not on the frozen copy). Own rows (no link) and
     * overridden rows keep their own column. null = the column is tenant-owned (or not rewritten): leave the query alone.
     * Codes / settings values are not rewritten (codes are immutable keys; `settings.value` is never filtered on).
     */
    public static function columnSql(string $section, string $column, string $ref): ?string
    {
        $db = DB::connection('tenant');
        $t = "`{$ref}`";
        $central = static fn (string $centralTable, string $link, string $expr, string $extra = '') => '(SELECT ' . $expr . ' FROM ' . CentralTable::name($centralTable)
            . " c WHERE c.`id` = {$t}.`{$link}`" . $extra . ' LIMIT 1)';

        return match ("{$section}.{$column}") {
            'languages.is_active' => "(CASE WHEN {$t}.`central_language_id` IS NULL THEN {$t}.`is_active` ELSE ({$t}.`is_active` = 1 AND COALESCE("
                . $central('languages', 'central_language_id', 'c.`is_active`') . ', 0) = 1) END)',
            'languages.sort_order', 'languages.name', 'languages.native_name', 'languages.direction' => 'COALESCE('
                . $central('languages', 'central_language_id', "c.`{$column}`", ' AND ' . self::live($db, 'languages')) . ", {$t}.`{$column}`)",
            'currencies.name' => 'COALESCE(' . $central('currencies', 'central_currency_id', 'c.`name`') . ", {$t}.`name`)",
            'currencies.symbol' => 'COALESCE(' . $central('currencies', 'central_currency_id', 'c.`sign`') . ", {$t}.`symbol`)",
            'currencies.conversion_rate' => 'COALESCE(' . $central('currencies', 'central_currency_id', 'c.`conversion_rate`') . ", {$t}.`conversion_rate`)",
            'payment-gateways.is_active' => 'COALESCE(' . $central('payment_gateways', 'central_payment_gateway_id', "IF(c.`status` = 'active', 1, 0)") . ", {$t}.`is_active`)",
            'payment-gateways.hide' => 'COALESCE(' . $central('payment_gateways', 'central_payment_gateway_id', "IF(c.`status` = 'active', 0, 1)") . ", {$t}.`hide`)",
            'payment-gateways.name' => 'COALESCE(' . $central('payment_gateways', 'central_payment_gateway_id', 'c.`name`') . ", {$t}.`name`)",
            'themes.name', 'themes.slug' => 'COALESCE(' . $central('templates', 'central_theme_id', "c.`{$column}`") . ", {$t}.`{$column}`)",
            'themes.is_universal' => "(CASE WHEN {$t}.`central_theme_id` IS NULL THEN {$t}.`is_universal` ELSE (NOT EXISTS (SELECT 1 FROM "
                . CentralTable::name('template_country') . " tc WHERE tc.`template_id` = {$t}.`central_theme_id`)) END)",
            'email-templates.name', 'email-templates.action' => 'COALESCE(' . $central('email_templates', 'central_email_template_id', "c.`{$column}`") . ", {$t}.`{$column}`)",
            'email-templates.is_active' => "(CASE WHEN {$t}.`central_email_template_id` IS NULL OR {$t}.`central_overridden` = 1 THEN {$t}.`is_active` ELSE COALESCE("
                . $central('email_templates', 'central_email_template_id', "IF(c.`status` = 'active', 1, 0)") . ", {$t}.`is_active`) END)",
            'pages.active' => "(CASE WHEN {$t}.`source` <> 'central' THEN {$t}.`active` ELSE ({$t}.`active` = 1 AND EXISTS (SELECT 1 FROM "
                . CentralTable::name('static_pages') . " c WHERE c.`slug` = {$t}.`slug` AND c.`status` IN ('active', 'published') AND " . self::live($db, 'static_pages') . ')) END)',
            default => null,
        };
    }

    /**
     * Rewrites the caller's WHERE (Basic / In / NotIn, nested groups too) and ORDER BY clauses on central-owned columns
     * to columnSql(). Bindings keep their count and order (a Basic where becomes "(expr) op ?" with the same value).
     */
    public static function rewriteCentralColumns(\Illuminate\Database\Query\Builder $query, string $section, string $table, string $ref): void
    {
        $match = static function (mixed $column) use ($table, $ref): ?string {
            if (! is_string($column)) {
                return null;
            }
            $column = str_replace('`', '', $column);
            if (! str_contains($column, '.')) {
                return $column;
            }
            [$prefix, $bare] = explode('.', $column, 2);

            return $prefix === $table || $prefix === $ref ? $bare : null;
        };

        $rewriteWheres = static function (\Illuminate\Database\Query\Builder $q) use (&$rewriteWheres, $match, $section, $ref): void {
            foreach ($q->wheres as $i => $where) {
                $type = $where['type'] ?? '';
                if ($type === 'Nested' && isset($where['query'])) {
                    $rewriteWheres($where['query']);

                    continue;
                }
                if (! in_array($type, ['Basic', 'In', 'NotIn'], true) || ($bare = $match($where['column'] ?? null)) === null
                    || ($expr = self::columnSql($section, $bare, $ref)) === null) {
                    continue;
                }
                if ($type === 'Basic') {
                    if ($where['value'] instanceof \Illuminate\Contracts\Database\Query\Expression) {
                        continue;
                    }
                    $operator = in_array(strtolower((string) $where['operator']), ['=', '<', '>', '<=', '>=', '<>', '!=', 'like', 'not like'], true) ? $where['operator'] : null;
                    if ($operator === null) {
                        continue;
                    }
                    $q->wheres[$i] = ['type' => 'raw', 'sql' => "{$expr} {$operator} ?", 'boolean' => $where['boolean']];
                } else {
                    $values = array_values((array) $where['values']);
                    if ($values === [] || array_filter($values, fn ($v) => $v instanceof \Illuminate\Contracts\Database\Query\Expression) !== []) {
                        continue;
                    }
                    $q->wheres[$i] = ['type' => 'raw', 'sql' => $expr . ($type === 'In' ? ' IN (' : ' NOT IN (') . implode(', ', array_fill(0, count($values), '?')) . ')', 'boolean' => $where['boolean']];
                }
            }
        };
        $rewriteWheres($query);

        foreach ((array) $query->orders as $i => $order) {
            if (! isset($order['column']) || ($bare = $match($order['column'])) === null || ($expr = self::columnSql($section, $bare, $ref)) === null) {
                continue;
            }
            $direction = strtolower((string) ($order['direction'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';
            $query->orders[$i] = ['type' => 'Raw', 'sql' => "{$expr} {$direction}"];
        }
    }

    /** theme_country rows of a country the central template no longer allows are hidden (empty pivot = all allowed). */
    public static function themeCountryVisibleSql(string $ref = 'theme_country'): string
    {
        $tc = CentralTable::name('template_country');

        return "(NOT EXISTS (SELECT 1 FROM {$tc} t0 WHERE t0.`template_id` = (SELECT th.`central_theme_id` FROM `themes` th WHERE th.`id` = `{$ref}`.`theme_id`))
                 OR EXISTS (SELECT 1 FROM {$tc} t1 WHERE t1.`template_id` = (SELECT th2.`central_theme_id` FROM `themes` th2 WHERE th2.`id` = `{$ref}`.`theme_id`) AND t1.`country_id` = `{$ref}`.`country_id`))";
    }

    // ---------------------------------------------------------------------------------------------------------
    // Pages: central translations by language code
    // ---------------------------------------------------------------------------------------------------------

    /**
     * Central page translations of one slug as [field => [locale code => value]] (fields title / body), read live from
     * the cached `pages` payload (central `translations` of the StaticPage morph, language resolved by CODE).
     *
     * @return array<string, array<string, string>>
     */
    public static function pageTranslations(string $slug): array
    {
        return self::map('pages')[$slug]['translations'] ?? [];
    }

    /**
     * Locale codes of a central page that the platform manages (central has a title or body in that language): the
     * panel shows them read-only and the save path skips them. Empty for own pages / sync mode.
     *
     * @return string[]
     */
    public static function pageLockedLocales(?string $slug, ?string $source): array
    {
        if ($slug === null || $source !== 'central' || ! self::enabled('pages')) {
            return [];
        }
        $codes = [];
        foreach (self::pageTranslations($slug) as $byCode) {
            $codes += array_fill_keys(array_keys($byCode), true);
        }

        return array_map('strval', array_keys($codes));
    }
}
