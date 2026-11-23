<?php

namespace App\Services\Catalog;

use App\Models\AppSetting as CentralAppSetting;
use App\Models\Template as CentralTemplate;
use App\Models\Tenant\PaymentGateway;
use App\Repositories\Tenant\Catalog\ConfigCatalog;
use App\Support\Catalog\CentralTable;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;

/**
 * Dedup plan phase 6: the ONLY thing the config sections still write for a tenant on `central`. Inserts the identity
 * rows (local id for the FKs) of central rows the tenant does not have yet, and claims an existing row of the same
 * natural key (code / name / slug). Never updates central-owned values, never deletes (a gone central row is hidden by
 * ConfigVisibleScope; deletion is Phase 7). Runs from the section sync entry points and from `catalog:dedup-migrate`.
 * Runs INSIDE the tenant; idempotent.
 */
final class ConfigIdentity
{
    private ConnectionInterface $db;

    public function __construct()
    {
        $this->db = DB::connection('tenant');
    }

    /** @return array<string, int> counts (inserted / claimed) */
    public function ensure(string $section, bool $dryRun = false): array
    {
        return match ($section) {
            'languages' => $this->languages($dryRun),
            'currencies' => $this->currencies($dryRun),
            'payment-gateways' => $this->gateways($dryRun),
            'themes' => $this->themes($dryRun),
            'email-templates' => $this->emailTemplates($dryRun),
            'settings' => $this->settings($dryRun),
            'pages' => $this->pages($dryRun),
            default => [],
        };
    }

    private function languages(bool $dryRun): array
    {
        $central = ConfigCatalog::fresh('languages');
        $rows = $this->db->table('languages')->get(['id', 'code', 'central_language_id']);
        $byCentral = $rows->whereNotNull('central_language_id')->pluck('id', 'central_language_id');
        $byCode = $rows->whereNull('central_language_id')->pluck('id', 'code');
        $hasDefault = $this->db->table('languages')->where('is_default', true)->exists();
        $now = now();
        $counts = ['inserted' => 0, 'claimed' => 0];

        foreach ($central as $id => $c) {
            if ($byCentral->has($id)) {
                continue;
            }
            if ($byCode->has($c['code'])) {
                $counts['claimed']++;
                $dryRun || $this->db->table('languages')->where('id', $byCode[$c['code']])->update(['central_language_id' => $id, 'updated_at' => $now]);

                continue;
            }
            $counts['inserted']++;
            if (! $dryRun) {
                $default = ! $hasDefault && (int) $c['central_default'] === 1;
                $hasDefault = $hasDefault || $default;
                $this->db->table('languages')->insert([
                    'central_language_id' => $id, 'code' => $c['code'], 'name' => $c['name'], 'native_name' => $c['native_name'],
                    'direction' => $c['direction'], 'is_active' => (int) $c['central_active'], 'is_default' => (int) $default,
                    'sort_order' => $c['sort_order'], 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }

        return $counts;
    }

    private function currencies(bool $dryRun): array
    {
        $central = ConfigCatalog::fresh('currencies');
        $rows = $this->db->table('currencies')->get(['id', 'code', 'central_currency_id']);
        $byCentral = $rows->whereNotNull('central_currency_id')->pluck('id', 'central_currency_id');
        $byCode = $rows->whereNull('central_currency_id')->pluck('id', 'code');
        $hasDefault = $this->db->table('currencies')->where('is_default', true)->exists();
        $defaultCode = strtoupper((string) (CentralAppSetting::query()->where('key', 'default_currency')->value('value') ?? 'USD'));
        $now = now();
        $counts = ['inserted' => 0, 'claimed' => 0];

        foreach ($central as $id => $c) {
            if ($byCentral->has($id)) {
                continue;
            }
            $code = strtoupper((string) ($c['code'] ?: substr(preg_replace('/[^A-Z]/', '', strtoupper((string) $c['name'])) ?: 'CUR', 0, 6)));
            if ($byCode->has($code)) {
                $counts['claimed']++;
                $dryRun || $this->db->table('currencies')->where('id', $byCode[$code])->update(['central_currency_id' => $id, 'updated_at' => $now]);

                continue;
            }
            $counts['inserted']++;
            if (! $dryRun) {
                $default = ! $hasDefault && $code === $defaultCode;
                $hasDefault = $hasDefault || $default;
                $this->db->table('currencies')->insert([
                    'central_currency_id' => $id, 'code' => $code, 'name' => $c['name'], 'symbol' => $c['symbol'],
                    'conversion_rate' => $c['conversion_rate'], 'is_active' => 1, 'is_default' => (int) $default,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }

        return $counts;
    }

    private function gateways(bool $dryRun): array
    {
        $central = ConfigCatalog::fresh('payment-gateways');
        $have = $this->db->table('payment_gateways')->whereNotNull('central_payment_gateway_id')->pluck('id', 'central_payment_gateway_id');
        $now = now();
        $counts = ['inserted' => 0];

        foreach ($central as $id => $c) {
            if ($have->has($id)) {
                continue;
            }
            $counts['inserted']++;
            if ($dryRun) {
                continue;
            }
            $keys = (array) json_decode((string) $c['required_keys'], true);
            $row = new PaymentGateway([
                'name' => $c['name'], 'code' => $c['code'], 'is_active' => (bool) $c['is_active'], 'required_keys' => $keys,
                'hide' => (bool) $c['hide'], 'mode' => \App\Enums\PaymentGatewayMode::Test->value, 'use_own' => false,
            ]);
            $row->required_values = array_fill_keys($keys, '');
            $this->db->table('payment_gateways')->insert($row->getAttributes() + ['central_payment_gateway_id' => $id, 'created_at' => $now, 'updated_at' => $now]);
        }

        return $counts;
    }

    private function themes(bool $dryRun): array
    {
        $central = ConfigCatalog::fresh('themes');
        $existing = $this->db->table('themes')->whereNotNull('central_theme_id')->pluck('id', 'central_theme_id');
        $now = now();
        $counts = ['inserted' => 0, 'theme_countries_inserted' => 0];
        $templates = CentralTemplate::query()->with('previewFile')->whereIn('id', array_keys($central))->get()->keyBy('id');

        foreach ($central as $id => $c) {
            if ($existing->has($id)) {
                continue;
            }
            $counts['inserted']++;
            if ($dryRun) {
                continue;
            }
            $existing[$id] = $this->db->table('themes')->insertGetId([
                'central_theme_id' => $id, 'name' => $c['name'], 'slug' => $c['slug'], 'is_universal' => (int) $c['is_universal'],
                'is_active' => 0, 'preview_path' => $templates->get($id)?->previewFile?->full_path, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        if (! $dryRun && ! $this->db->table('themes')->where('is_active', true)->exists()) {
            $default = $templates->firstWhere('is_default', true)?->id ?? $templates->firstWhere('is_active', true)?->id ?? $templates->first()?->id;
            if ($default !== null && $existing->has($default)) {
                $this->db->table('themes')->where('id', $existing[$default])->update(['is_active' => 1]);
            }
        }

        // theme_country: one row per allowed country (empty central pivot = every country), enabled by default.
        $allCountries = null;
        foreach ($existing as $centralId => $themeId) {
            if (! isset($central[$centralId])) {
                continue;
            }
            $allowed = $this->db->table(CentralTable::qualified('template_country'))->where('template_id', $centralId)->pluck('country_id')->all();
            $countries = $allowed === []
                ? ($allCountries ??= $this->db->table(CentralTable::qualified('countries'))->get(['id', 'iso2', 'name', 'flag_emoji']))
                : $this->db->table(CentralTable::qualified('countries'))->whereIn('id', $allowed)->get(['id', 'iso2', 'name', 'flag_emoji']);
            $have = $this->db->table('theme_country')->where('theme_id', $themeId)->pluck('country_id')->flip();
            foreach ($countries as $country) {
                if ($have->has($country->id)) {
                    continue;
                }
                $counts['theme_countries_inserted']++;
                $dryRun || $this->db->table('theme_country')->insert([
                    'theme_id' => $themeId, 'country_id' => $country->id, 'iso2' => $country->iso2, 'name' => $country->name,
                    'flag_emoji' => $country->flag_emoji, 'is_enabled' => 1, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }

        return $counts;
    }

    private function emailTemplates(bool $dryRun): array
    {
        $central = ConfigCatalog::fresh('email-templates');
        $rows = $this->db->table('email_templates')->get(['id', 'name', 'central_email_template_id']);
        $byCentral = $rows->whereNotNull('central_email_template_id')->pluck('id', 'central_email_template_id');
        $byName = $rows->whereNull('central_email_template_id')->pluck('id', 'name');
        $now = now();
        $counts = ['inserted' => 0, 'claimed' => 0, 'translations_inserted' => 0];

        foreach ($central as $id => $c) {
            $tenantId = $byCentral->get($id);
            if ($tenantId === null && $byName->has($c['name'])) {
                $counts['claimed']++;
                $tenantId = $byName[$c['name']];
                $dryRun || $this->db->table('email_templates')->where('id', $tenantId)->update(['central_email_template_id' => $id, 'updated_at' => $now]);
            } elseif ($tenantId === null) {
                $counts['inserted']++;
                if ($dryRun) {
                    continue;
                }
                $tenantId = $this->db->table('email_templates')->insertGetId([
                    'central_email_template_id' => $id, 'name' => $c['name'], 'action' => $c['action'], 'subject' => $c['subject'],
                    'body' => $c['body'], 'is_active' => (int) $c['is_active'], 'central_overridden' => 0, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }

            $locales = $this->db->table('email_template_translations')->where('email_template_id', $tenantId)->pluck('locale')->flip();
            foreach ($c['translations'] as $locale => $t) {
                if ($locales->has($locale)) {
                    continue;
                }
                $counts['translations_inserted']++;
                $dryRun || $this->db->table('email_template_translations')->insert([
                    'email_template_id' => $tenantId, 'locale' => $locale, 'subject' => $t['subject'], 'body' => $t['body'],
                    'central_overridden' => 0, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }

        return $counts;
    }

    private function settings(bool $dryRun): array
    {
        $central = ConfigCatalog::fresh('settings');
        $have = $this->db->table('settings')->pluck('id', 'name');
        $types = CentralAppSetting::query()->pluck('type', 'key');
        $groupsOf = fn (string $key) => str_starts_with($key, 'mail_') ? 'mail' : 'general';
        $defaultLocale = (string) (CentralAppSetting::query()->where('key', 'default_language')->value('value') ?? 'en');
        $languageId = $this->db->table('languages')->where('code', $defaultLocale)->value('id');
        $now = now();
        $counts = ['inserted' => 0];

        foreach ($central as $key => $c) {
            // fresh('settings') already leaves out keys a tenant never inherits (mail_*, secrets, tenant defaults:
            // ConfigCatalog::inheritsSetting), so a central credential never gets a tenant row in central mode.
            if ($have->has($key) || ! ConfigCatalog::inheritsSetting((string) $key, $types[$key] ?? null)) {
                continue;
            }
            $counts['inserted']++;
            if ($dryRun) {
                continue;
            }
            $id = $this->db->table('settings')->insertGetId([
                'name' => $key, 'value' => $c['value'] === null ? null : mb_substr((string) $c['value'], 0, 255), 'type' => $this->settingType($types[$key] ?? 'string'),
                'group' => $groupsOf((string) $key), 'options' => null, 'central_overridden' => 0, 'created_at' => $now, 'updated_at' => $now,
            ]);
            if ($languageId) {
                $this->db->table('translations')->insert([
                    'language_id' => $languageId, 'translatable_type' => \App\Models\Tenant\Setting::class, 'translatable_id' => $id,
                    'field' => 'title', 'value' => str($key)->replace('_', ' ')->headline()->toString(), 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }

        return $counts;
    }

    private function pages(bool $dryRun): array
    {
        $central = ConfigCatalog::fresh('pages');
        $have = $this->db->table('pages')->get(['slug', 'source'])->pluck('source', 'slug');
        $now = now();
        $counts = ['inserted' => 0, 'claimed' => 0];

        foreach ($central as $slug => $c) {
            $active = in_array($c['status'], ['active', 'published'], true) ? 1 : 0;
            if (! $have->has($slug)) {
                $counts['inserted']++;
                $dryRun || $this->db->table('pages')->insert(['slug' => $slug, 'source' => 'central', 'active' => $active, 'created_at' => $now, 'updated_at' => $now]);
            } elseif ($have[$slug] !== 'central') {
                $counts['claimed']++; // same as the sync: a row already using a central slug is claimed
                $dryRun || $this->db->table('pages')->where('slug', $slug)->update(['source' => 'central', 'updated_at' => $now]);
            }
        }

        return $counts;
    }

    private function settingType(mixed $central): string
    {
        return match ($central instanceof \BackedEnum ? $central->value : (string) $central) {
            'boolean' => 'bool', 'integer' => 'number', 'json' => 'json', 'secret' => 'password', default => 'string',
        };
    }
}
