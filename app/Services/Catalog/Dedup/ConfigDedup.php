<?php

namespace App\Services\Catalog\Dedup;

use App\Models\Tenant;
use App\Repositories\Tenant\Catalog\ConfigCatalog;
use App\Services\Catalog\ConfigIdentity;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;

/**
 * `catalog:dedup-migrate <config section>` (dedup plan phase 6), shared by languages, currencies, settings,
 * payment_gateways, themes, email_templates and pages. Verdict: the tenant row stays as the identity / override row
 * (see ConfigCatalog), so migrate only
 *
 *  1. classifies every tenant row (own / equal to central / stale copy / orphan whose central row is gone),
 *  2. for settings and email templates sets `central_overridden = 1` on rows whose value differs from central
 *     (insert-only sync => a differing value is the vendor's edit; a stale copy after a central edit cannot be told
 *     apart, so the vendor's text is KEPT: nothing is lost, "re-sync from central" drops it),
 *  3. inserts the missing identity rows (ConfigIdentity).
 *
 * verify = shadow read: the merged (overlay) value of every central-owned column must equal the synced copy, and no
 * visible central row may lack an identity row. A stale copy (sync lag) is a diff: run the section sync first.
 * Additive and idempotent; nothing is deleted (phase 7).
 */
abstract class ConfigDedup implements DedupSection
{
    private const EXAMPLES = 200;

    /** Columns whose copy differs from the merged value on purpose (derived, not drift). */
    private const IGNORED = ['languages' => ['is_active']];

    /** ConfigCatalog section name (hyphenated). */
    abstract protected function section(): string;

    public function name(): string
    {
        return str_replace('-', '_', $this->section());
    }

    private function db(): ConnectionInterface
    {
        return DB::connection('tenant');
    }

    public function notReadyReason(): ?string
    {
        return ConfigCatalog::schemaReady($this->section(), $this->db()) ? null : 'run tenants:migrate (phase 6 config markers missing)';
    }

    public function migrate(Tenant $tenant, bool $dryRun, array $options = []): array
    {
        $section = $this->section();
        [$table, $link] = ConfigCatalog::LINKS[$section];
        $central = ConfigCatalog::fresh($section);
        $counts = ['rows' => 0, 'own' => 0, 'equal' => 0, 'stale' => 0, 'orphan' => 0, 'overridden_marked' => 0];
        $examples = [];

        foreach ($this->db()->table($table)->get() as $row) {
            $counts['rows']++;
            $array = (array) $row;
            $key = $array[$link] ?? null;
            if ($key === null || ($section === 'pages' && ($array['source'] ?? 'central') !== 'central')) {
                $counts['own']++;

                continue;
            }
            if (! isset($central[in_array($section, ['settings', 'pages'], true) ? (string) $key : (int) $key])) {
                // settings: a key central does not have is the vendor's own setting, not an orphan.
                $section === 'settings' ? $counts['own']++ : $counts['orphan']++;
                if ($section !== 'settings' && count($examples) < self::EXAMPLES) {
                    $examples[] = ['class' => 'orphan', 'row' => $array['id'] ?? $key];
                }

                continue;
            }

            $diff = $this->differences($section, $array, $central, true);
            if ($diff === []) {
                $counts['equal']++;

                continue;
            }

            if ($this->hasMarker($section)) {
                // A differing value is the vendor's: keep it (marker), never lose text.
                if ((int) ($array['central_overridden'] ?? 0) === 0) {
                    $counts['overridden_marked']++;
                    $dryRun || $this->db()->table($table)->where('id', $array['id'])->update(['central_overridden' => 1]);
                }
                if (count($examples) < self::EXAMPLES) {
                    $examples[] = ['class' => 'overridden', 'row' => $array['id'] ?? $key, 'columns' => array_keys($diff)];
                }

                continue;
            }

            $counts['stale']++;
            if (count($examples) < self::EXAMPLES) {
                $examples[] = ['class' => 'stale', 'row' => $array['id'] ?? $key, 'columns' => array_keys($diff)];
            }
        }

        if ($section === 'email-templates') {
            $counts += $this->migrateTranslationMarkers($dryRun);
        }

        $identity = (new ConfigIdentity())->ensure($section, $dryRun);
        ConfigCatalog::flushMemo();

        return ['counts' => $counts + $identity, 'examples' => $examples, 'note' => 'Identity rows stay (local FKs); copies are not deleted (phase 7).'];
    }

    public function verify(Tenant $tenant): array
    {
        $section = $this->section();
        [$table, $link] = ConfigCatalog::LINKS[$section];
        $central = ConfigCatalog::fresh($section);
        $linked = [];
        $drift = 0;
        $examples = [];

        foreach ($this->db()->table($table)->get() as $row) {
            $array = (array) $row;
            $key = $array[$link] ?? null;
            if ($key === null || ($section === 'pages' && ($array['source'] ?? 'central') !== 'central')) {
                continue;
            }
            $linked[in_array($section, ['settings', 'pages'], true) ? (string) $key : (int) $key] = true;

            if (! isset($central[in_array($section, ['settings', 'pages'], true) ? (string) $key : (int) $key])) {
                continue; // orphan / own setting: hidden or tenant-only, not drift
            }
            if (($d = $this->differences($section, $array, $central, false)) !== []) {
                $drift++;
                if (count($examples) < self::EXAMPLES) {
                    $examples[] = ['row' => $array['id'] ?? $key, 'columns' => array_keys($d)];
                }
            }
        }

        $missing = 0;
        foreach (array_keys($central) as $key) {
            $missing += isset($linked[$key]) ? 0 : 1;
        }

        $translationDrift = $section === 'email-templates' ? $this->emailTranslationDrift($central) : 0;

        return [
            'diffs' => $drift + $missing + $translationDrift,
            'counts' => ['rows' => count($linked), 'drift' => $drift, 'identity_missing' => $missing, 'translation_drift' => $translationDrift],
            'examples' => $examples,
        ];
    }

    private function hasMarker(string $section): bool
    {
        return in_array($section, ['settings', 'email-templates'], true);
    }

    /**
     * Columns where the synced copy differs from central's value. $ignoreMarker = migrate looks at the raw difference
     * (the marker is what it is about to set); verify honours the marker (an overridden row is expected to differ).
     *
     * @return array<string, array{copy: mixed, central: mixed}>
     */
    private function differences(string $section, array $row, array $central, bool $ignoreMarker): array
    {
        $copy = $row;
        if ($ignoreMarker) {
            $row['central_overridden'] = 0; // overlay as if untouched, to see what the copy lacks
        }
        $values = ConfigCatalog::overlayValues($section, $row, $central) ?? [];
        $diff = [];
        foreach ($values as $column => $value) {
            if (in_array($column, self::IGNORED[$section] ?? [], true) || ! array_key_exists($column, $copy)) {
                continue;
            }
            $trim = $section === 'settings' && $column === 'value'; // the identity copy is varchar(255)
            $a = $this->normalise($copy[$column]);
            $b = $this->normalise($value);
            if ($trim ? mb_substr($a, 0, 255) !== mb_substr($b, 0, 255) : $a !== $b) {
                $diff[$column] = ['copy' => $copy[$column], 'central' => $value];
            }
        }

        return $diff;
    }

    private function normalise(mixed $value): string
    {
        if (is_string($value) && $value !== '' && in_array($value[0], ['[', '{'], true)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                ksort($decoded);

                return json_encode($decoded, JSON_UNESCAPED_UNICODE);
            }
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_numeric($value)) {
            return rtrim(rtrim(number_format((float) $value, 6, '.', ''), '0'), '.');
        }

        return (string) $value;
    }

    /** @return array<string, int> */
    private function migrateTranslationMarkers(bool $dryRun): array
    {
        $central = ConfigCatalog::fresh('email-templates');
        $ids = $this->db()->table('email_templates')->whereNotNull('central_email_template_id')->pluck('central_email_template_id', 'id');
        $marked = 0;
        foreach ($this->db()->table('email_template_translations')->get() as $translation) {
            $centralId = $ids[$translation->email_template_id] ?? null;
            $c = $centralId === null ? null : ($central[(int) $centralId]['translations'][$translation->locale] ?? null);
            if ($c === null || (int) $translation->central_overridden === 1) {
                continue; // tenant-only locale: always the vendor's
            }
            if ($this->normalise($translation->subject) !== $this->normalise($c['subject']) || $this->normalise($translation->body) !== $this->normalise($c['body'])) {
                $marked++;
                $dryRun || $this->db()->table('email_template_translations')->where('id', $translation->id)->update(['central_overridden' => 1]);
            }
        }

        return ['translations_overridden_marked' => $marked];
    }

    private function emailTranslationDrift(array $central): int
    {
        $ids = $this->db()->table('email_templates')->whereNotNull('central_email_template_id')->pluck('central_email_template_id', 'id');
        $drift = 0;
        foreach ($this->db()->table('email_template_translations')->where('central_overridden', 0)->get() as $translation) {
            $centralId = $ids[$translation->email_template_id] ?? null;
            $c = $centralId === null ? null : ($central[(int) $centralId]['translations'][$translation->locale] ?? null);
            if ($c !== null && ($this->normalise($translation->subject) !== $this->normalise($c['subject']) || $this->normalise($translation->body) !== $this->normalise($c['body']))) {
                $drift++;
            }
        }

        return $drift;
    }
}
