<?php

namespace App\Services\Catalog\Dedup;

use App\Models\Tenant;
use App\Repositories\Tenant\Catalog\LabelCatalog;
use App\Support\Catalog\CentralTable;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;

/**
 * `catalog:dedup-migrate labels` (dedup plan phase 2). Verdict DROP: the tenant owns nothing about central labels,
 * so migrate writes NOTHING; it classifies the legacy copies for the report (copy equal to central / stale copy /
 * orphan copy whose central label is gone / pivot rows central does not have). verify = multiset diff of
 * (product, central label, text, translations, icon, active, sort_order) between the tenant copies (old read) and
 * LabelCatalog::membershipSql() (new read). A stale copy (sync lag) is a diff: run the labels sync first.
 */
final class LabelsDedup implements DedupSection
{
    private const EXAMPLES = 200;

    public function name(): string
    {
        return 'labels';
    }

    private function db(): ConnectionInterface
    {
        return DB::connection('tenant');
    }

    public function notReadyReason(): ?string
    {
        return null; // reads only existing phase -1/0 columns
    }

    public function migrate(Tenant $tenant, bool $dryRun, array $options = []): array
    {
        $central = $this->db()->table(CentralTable::qualified('product_labels') . ' as cl')
            ->get(['cl.id', 'cl.text', 'cl.translations', 'cl.icon', 'cl.active', 'cl.sort_order', ...($this->centralSoftDeletes() ? ['cl.deleted_at'] : [])])
            ->keyBy('id');
        $counts = ['copies' => 0, 'equal' => 0, 'stale' => 0, 'orphan' => 0, 'own_rows' => 0];
        $examples = [];

        foreach ($this->db()->table('product_labels')->get() as $copy) {
            if ($copy->central_product_label_id === null) {
                $counts['own_rows']++;

                continue;
            }
            $counts['copies']++;
            $c = $central->get((int) $copy->central_product_label_id);
            if ($c === null || (isset($c->deleted_at) && $c->deleted_at !== null)) {
                $counts['orphan']++;
                $examples[] = ['class' => 'orphan', 'label' => (int) $copy->id, 'central' => (int) $copy->central_product_label_id];
            } elseif ($this->fingerprint($copy) === $this->fingerprint($c)) {
                $counts['equal']++;
            } else {
                $counts['stale']++;
                if (count($examples) < self::EXAMPLES) {
                    $examples[] = ['class' => 'stale', 'label' => (int) $copy->id, 'central' => (int) $c->id];
                }
            }
        }

        return ['counts' => $counts + ['written' => 0], 'examples' => $examples, 'note' => 'labels = DROP: nothing to migrate; copies are deleted in phase 7.'];
    }

    public function verify(Tenant $tenant): array
    {
        $old = $this->tuples($this->db()->select(
            'SELECT t.`product_id`, l.`central_product_label_id` AS label_id, l.`text`, l.`translations`, l.`icon`, l.`active`, l.`sort_order`
               FROM `product_label_product` t JOIN `product_labels` l ON l.`id` = t.`product_label_id`
              WHERE l.`central_product_label_id` IS NOT NULL'
        ));
        $new = $this->tuples($this->db()->select(
            'SELECT m.`product_id`, m.`id` AS label_id, m.`text`, m.`translations`, m.`icon`, m.`active`, m.`sort_order` FROM (' . LabelCatalog::membershipSql($this->db()) . ') m'
        ));

        $onlyOld = $onlyNew = [];
        foreach ($old as $k => $n) {
            if (($d = $n - ($new[$k] ?? 0)) > 0) {
                $onlyOld[$k] = $d;
            }
        }
        foreach ($new as $k => $n) {
            if (($d = $n - ($old[$k] ?? 0)) > 0) {
                $onlyNew[$k] = $d;
            }
        }

        return [
            'diffs' => array_sum($onlyOld) + array_sum($onlyNew),
            'counts' => ['old_rows' => array_sum($old), 'new_rows' => array_sum($new), 'only_old' => array_sum($onlyOld), 'only_new' => array_sum($onlyNew)],
            'only_old' => array_slice(array_keys($onlyOld), 0, self::EXAMPLES),
            'only_new' => array_slice(array_keys($onlyNew), 0, self::EXAMPLES),
        ];
    }

    private function centralSoftDeletes(): bool
    {
        return \App\Support\Catalog\CatalogRefColumns::has($this->db(), 'product_labels', 'deleted_at', CentralTable::database());
    }

    private function fingerprint(object $row): string
    {
        return implode('|', [(string) $row->text, $this->json($row->translations), (string) $row->icon, (int) $row->active, (int) $row->sort_order]);
    }

    /** JSON compared by value (key order / spacing independent). */
    private function json(mixed $value): string
    {
        $decoded = is_string($value) ? json_decode($value, true) : $value;
        if (is_array($decoded)) {
            ksort($decoded);
        }

        return json_encode($decoded, JSON_UNESCAPED_UNICODE);
    }

    /** @return array<string, int> */
    private function tuples(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $key = (int) $row->product_id . '|' . (int) $row->label_id . '|' . $this->fingerprint($row);
            $out[$key] = ($out[$key] ?? 0) + 1;
        }

        return $out;
    }
}
