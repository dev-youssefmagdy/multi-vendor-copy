<?php

namespace App\Services\Catalog\Dedup;

use App\Models\Tenant;
use App\Models\Tenant\Catalog\MergedCategory;
use App\Models\Tenant\Category;
use App\Repositories\Tenant\Catalog\CategoryCatalog;
use App\Support\Catalog\CatalogRef;
use App\Support\Catalog\CatalogRefColumns;
use App\Support\Catalog\CatalogRefResolver;
use App\Support\Catalog\CatalogRefWriter;
use App\Support\Catalog\CatalogSource;
use App\Support\Catalog\CategoryKey;
use App\Support\Catalog\CentralTable;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;

/**
 * `catalog:dedup-migrate categories` (dedup plan phase 4, §5a). Verdict OVERRIDE for central categories (+ KEEP own).
 *
 * migrate (ADDITIVE, idempotent; copies and pivot rows are never changed or deleted, so `sync` keeps working):
 *  - `tenant_category_overrides`: the copy's active / featured / order_number when it differs from the central default
 *    (active 1, central is_featured / order_number) and the copy's thumb file id; an existing row is refreshed while
 *    the tenant is still on `sync` (vendor edits made before the flip), never after;
 *  - `tenant_category_product_overrides`: sort of a central pair whose tenant sort ≠ central sort (`source = central`),
 *    vendor links on copies central does not have (`source = tenant`, incl. own products);
 *  - typed parent of own categories (`parent_type` / `catalog_parent_id`; `parent_id` kept);
 *  - copies' translations in languages central lacks → `central_category` rows (central id);
 *  - phase-1 typed refs of `category_product` (NULL pairs only);
 *  - category ids embedded in configs (banners `url`, `mobile_app_settings.value`, `settings.value/options`,
 *    `tenant_notifications.data`: JSON keys category_id(s)/categoryId(s) and `?category_id=` URL params) → refs
 *    ("c:12" / "t:3"; readers accept refs in both modes). Unknown ids are kept and reported, never dropped.
 *  Reported: copies, own, orphan / hidden / out-of-scope copies, central categories without copy, central pairs
 *  missing in the copy pivot (sync lag: run the categories + products sync before verify).
 *
 * verify (read-only): shadow-read diff of the old read (synced tables, Category scope) vs CategoryCatalog: per category
 * key → parent key, active, featured, order_number, every translation (language code, field), thumb URL; membership
 * (key, product, sort) multiset; config ids still numeric and mappable. `diffs` must be 0 before the flip.
 */
final class CategoriesDedup implements DedupSection
{
    private const EXAMPLES = 200;

    /** [table, key column, value column, kind json|url|text] */
    private const CONFIGS = [
        ['banners', 'id', 'url', 'url'],
        ['mobile_app_settings', 'id', 'value', 'json'],
        ['settings', 'id', 'value', 'text'],
        ['settings', 'id', 'options', 'json'],
        ['tenant_notifications', 'id', 'data', 'json'],
    ];

    private const ID_KEYS = ['category_id', 'categoryid', 'category_ids', 'categoryids'];

    private int $staleCentralLinks = 0;

    public function name(): string
    {
        return 'categories';
    }

    private function db(): ConnectionInterface
    {
        return DB::connection('tenant');
    }

    public function notReadyReason(): ?string
    {
        return CategoryCatalog::schemaReady($this->db())
            ? null
            : 'tenant DB lacks the phase-4 category tables / columns: run `php artisan tenants:migrate` first';
    }

    // ── migrate ─────────────────────────────────────────────────────────────

    public function migrate(Tenant $tenant, bool $dryRun, array $options = []): array
    {
        $db = $this->db();
        CategoryCatalog::flushMemo();
        $stillSync = ! CatalogSource::isCentral($tenant, 'categories');
        $cc = CentralTable::name('categories');
        $visible = CategoryCatalog::centralVisible('c', $db);
        $live = CatalogRefColumns::has($db, 'categories', 'deleted_at', CentralTable::database()) ? 'c.`deleted_at` IS NULL' : '1 = 1';

        $counts = [
            'copies' => (int) $db->scalar('SELECT COUNT(*) FROM `categories` WHERE `central_category_id` IS NOT NULL'),
            'own' => (int) $db->scalar('SELECT COUNT(*) FROM `categories` WHERE `central_category_id` IS NULL'),
            'orphan_copies' => (int) $db->scalar("SELECT COUNT(*) FROM `categories` t LEFT JOIN {$cc} c ON c.`id` = t.`central_category_id`
                 WHERE t.`central_category_id` IS NOT NULL AND (c.`id` IS NULL OR NOT ({$live}))"),
            'hidden_copies' => (int) $db->scalar("SELECT COUNT(*) FROM `categories` t JOIN {$cc} c ON c.`id` = t.`central_category_id`
                 WHERE {$live} AND c.`status` <> 'published'"),
            'out_of_scope_copies' => (int) $db->scalar("SELECT COUNT(*) FROM `categories` t JOIN {$cc} c ON c.`id` = t.`central_category_id`
                 WHERE {$live} AND c.`status` = 'published' AND NOT {$visible}"),
            'central_without_copy' => (int) $db->scalar("SELECT COUNT(*) FROM {$cc} c WHERE {$visible}
                 AND NOT EXISTS (SELECT 1 FROM `categories` t WHERE t.`central_category_id` = c.`id`)"),
            'pairs_missing_in_copy' => $this->pairsMissingInCopy(),
        ];

        $written = ['overrides_written' => 0, 'overrides_refreshed' => 0, 'sort_overrides' => 0, 'links_written' => 0,
            'typed_parents' => 0, 'tenant_only_translations' => 0];

        // Overrides of the copies' vendor-owned fields.
        $ownType = Category::class;
        $copies = $db->select(
            "SELECT t.`id`, t.`central_category_id` AS cid, t.`active`, t.`featured`, t.`order_number`, c.`is_featured`, c.`order_number` AS c_order,
                    (SELECT MIN(f.`id`) FROM `files` f WHERE f.`model_type` = ? AND f.`model_id` = t.`id` AND f.`key` = 'thumb') AS thumb
               FROM `categories` t JOIN {$cc} c ON c.`id` = t.`central_category_id` AND {$live}
              WHERE t.`id` = (SELECT MIN(x.`id`) FROM `categories` x WHERE x.`central_category_id` = t.`central_category_id`)",
            [$ownType],
        );
        $existing = [];
        foreach ($db->table(CategoryCatalog::OVERRIDES)->get() as $row) {
            $existing[(int) $row->central_category_id] = $row;
        }
        $now = now();
        foreach ($copies as $copy) {
            $values = [
                'active' => (bool) $copy->active ? null : false,
                'featured' => (bool) $copy->featured === (bool) $copy->is_featured ? null : (bool) $copy->featured,
                'order_number' => (int) $copy->order_number === (int) $copy->c_order ? null : (int) $copy->order_number,
                'thumb_file_id' => $copy->thumb === null ? null : (int) $copy->thumb,
            ];
            $row = $existing[(int) $copy->cid] ?? null;
            if ($row === null) {
                if (array_filter($values, fn ($v) => $v !== null) === []) {
                    continue;
                }
                $written['overrides_written']++;
                if (! $dryRun) {
                    $db->table(CategoryCatalog::OVERRIDES)->insert($values + ['central_category_id' => (int) $copy->cid, 'created_at' => $now, 'updated_at' => $now]);
                }
            } elseif ($stillSync && $this->differs($row, $values)) {
                $written['overrides_refreshed']++;
                if (! $dryRun) {
                    $db->table(CategoryCatalog::OVERRIDES)->where('central_category_id', (int) $copy->cid)->update($values + ['updated_at' => $now]);
                }
            }
        }

        // Pairs on copies: sort overrides of central pairs + vendor links central lacks.
        [$sorts, $links] = $this->pairChanges();
        foreach ([[$sorts, 'central', 'sort_overrides'], [$links, 'tenant', 'links_written']] as [$pairs, $source, $counter]) {
            foreach ($pairs as $pair) {
                $query = $db->table(CategoryCatalog::PAIR_OVERRIDES)->where('category_type', 'central')->where('category_id', $pair['category_id'])
                    ->where('product_type', $pair['product_type'])->where('product_id', $pair['product_id']);
                $row = $query->first();
                if ($row !== null && (! $stillSync || ((int) $row->sort_order === $pair['sort_order'] && $row->source === $source && ! $row->hidden))) {
                    continue;
                }
                $written[$counter]++;
                if ($dryRun) {
                    continue;
                }
                $row === null
                    ? $db->table(CategoryCatalog::PAIR_OVERRIDES)->insert([
                        'category_type' => 'central', 'category_id' => $pair['category_id'], 'product_type' => $pair['product_type'],
                        'product_id' => $pair['product_id'], 'source' => $source, 'sort_order' => $pair['sort_order'], 'hidden' => false,
                        'created_at' => $now, 'updated_at' => $now,
                    ])
                    : $query->update(['source' => $source, 'sort_order' => $pair['sort_order'], 'hidden' => false, 'updated_at' => $now]);
            }
        }

        // Typed parents of own categories.
        foreach ($db->select(
            'SELECT o.`id`, p.`id` AS pid, p.`central_category_id` AS pcid FROM `categories` o JOIN `categories` p ON p.`id` = o.`parent_id`
              WHERE o.`central_category_id` IS NULL AND o.`parent_type` IS NULL'
        ) as $own) {
            $written['typed_parents']++;
            if (! $dryRun) {
                $db->table('categories')->where('id', $own->id)->update($own->pcid !== null
                    ? ['parent_type' => 'central', 'catalog_parent_id' => (int) $own->pcid]
                    : ['parent_type' => 'tenant', 'catalog_parent_id' => (int) $own->pid]);
            }
        }

        // Tenant-only-language translations of copies → central_category rows.
        $centralCodes = CategoryCatalog::centralLocales();
        $rows = $db->table('translations as t')
            ->join('categories as c', 'c.id', '=', 't.translatable_id')
            ->join('languages as l', 'l.id', '=', 't.language_id')
            ->where('t.translatable_type', $ownType)
            ->whereNotNull('c.central_category_id')
            ->when($centralCodes !== [], fn ($q) => $q->whereNotIn('l.code', $centralCodes))
            ->whereNotExists(fn ($q) => $q->from('translations as x')
                ->where('x.translatable_type', CategoryCatalog::CENTRAL_TRANSLATION_TYPE)
                ->whereColumn('x.translatable_id', 'c.central_category_id')
                ->whereColumn('x.language_id', 't.language_id')
                ->whereColumn('x.field', 't.field'))
            ->orderBy('c.id')
            ->get(['c.central_category_id', 't.language_id', 't.field', 't.value']);
        $seen = [];
        foreach ($rows as $row) {
            $key = $row->central_category_id . '|' . $row->language_id . '|' . $row->field;
            if (isset($seen[$key])) {
                continue; // duplicate copies of one central category: the first (lowest id) wins, like the sync
            }
            $seen[$key] = true;
            $written['tenant_only_translations']++;
            if (! $dryRun) {
                $db->table('translations')->insert([
                    'language_id' => (int) $row->language_id, 'translatable_type' => CategoryCatalog::CENTRAL_TRANSLATION_TYPE,
                    'translatable_id' => (int) $row->central_category_id, 'field' => $row->field, 'value' => $row->value,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }

        $refs = (new CatalogRefWriter(CatalogRefResolver::on($db), $dryRun))->fillTable('category_product');
        $config = $this->rewriteConfigs($dryRun);

        if (! $dryRun) {
            \App\Support\CacheVersion::bumpMany(['Category', 'Product', 'Translation']);
        }

        $counts['stale_central_links'] = $this->staleCentralLinks;

        return [
            'counts' => $counts + $written + [
                'pivot_refs_filled' => (int) ($refs['filled'] ?? 0),
                'pivot_refs_pending' => (int) ($refs['pending'] ?? 0),
                'config_refs_rewritten' => $config['rewritten'],
                'config_unknown_ids' => count($config['unknown']),
            ],
            'unmapped' => ['category_product' => $refs['unmapped'] ?? [], 'configs' => array_slice($config['unknown'], 0, self::EXAMPLES)],
            'config_changes' => array_slice($config['changes'], 0, self::EXAMPLES),
            'note' => 'categories = OVERRIDE: copies and category_product rows are kept (rollback = flip back to sync); deleted in phase 7.',
        ];
    }

    private function differs(object $row, array $values): bool
    {
        foreach ($values as $field => $value) {
            $current = $row->{$field};
            if ($current === null ? $value !== null : ($value === null || (int) $current !== (int) $value)) {
                return true;
            }
        }

        return false;
    }

    /** Central pairs of carried products in merged central categories without a copy pivot row. */
    private function pairsMissingInCopy(): int
    {
        $db = $this->db();
        $ccp = CentralTable::name('category_product');
        $cc = CentralTable::name('categories');

        return (int) $db->scalar(
            "SELECT COUNT(*) FROM {$ccp} cp JOIN {$cc} c ON c.`id` = cp.`category_id` AND " . CategoryCatalog::centralVisible('c', $db) . "
               JOIN `products` p ON p.`central_product_id` = cp.`product_id` AND {$this->notOwn('p')}
              WHERE NOT EXISTS (SELECT 1 FROM `category_product` t JOIN `categories` k ON k.`id` = t.`category_id`
                                 WHERE k.`central_category_id` = cp.`category_id` AND t.`product_id` = p.`id`)"
        );
    }

    /**
     * Copy pivot rows → [sort overrides of central pairs (tenant sort ≠ central sort), vendor links central lacks].
     *
     * @return array{0: list<array>, 1: list<array>}
     */
    private function pairChanges(): array
    {
        $db = $this->db();
        $ccp = CentralTable::name('category_product');
        $isCentral = "({$this->notOwn('p')} AND p.`central_product_id` IS NOT NULL)";

        $hasSource = CatalogRefColumns::has($db, 'category_product', 'source');
        $source = $hasSource ? 't.`source`' : "'tenant'";
        $sorts = $links = [];
        $this->staleCentralLinks = 0;
        foreach ($db->select(
            "SELECT k.`central_category_id` AS cid, t.`sort_order`, {$source} AS link_source, p.`id` AS pid, CASE WHEN {$isCentral} THEN p.`central_product_id` END AS cpid,
                    (SELECT cp.`sort_order` FROM {$ccp} cp WHERE cp.`category_id` = k.`central_category_id` AND cp.`product_id` = p.`central_product_id` AND {$isCentral}) AS central_sort
               FROM `category_product` t
               JOIN `categories` k ON k.`id` = t.`category_id` AND k.`central_category_id` IS NOT NULL
               JOIN `products` p ON p.`id` = t.`product_id`"
        ) as $row) {
            $product = $row->cpid !== null ? ['product_type' => 'central', 'product_id' => (int) $row->cpid] : ['product_type' => 'tenant', 'product_id' => (int) $row->pid];
            $pair = ['category_id' => (int) $row->cid, 'sort_order' => (int) $row->sort_order] + $product;
            if ($row->central_sort === null) {
                // A sync-made link central no longer has is stale (the next sync detaches it), not a vendor choice.
                $row->link_source === 'central' ? $this->staleCentralLinks++ : $links[] = $pair;
            } elseif ((int) $row->central_sort !== (int) $row->sort_order) {
                $sorts[] = $pair;
            }
        }

        return [$sorts, $links];
    }

    private function notOwn(string $alias): string
    {
        return CatalogRefColumns::has($this->db(), 'products', 'is_own_product') ? "COALESCE({$alias}.`is_own_product`, 0) = 0" : '1 = 1';
    }

    // ── configs ─────────────────────────────────────────────────────────────

    /** @return array{rewritten: int, unknown: list<array>, changes: list<array>, pending: int} */
    private function rewriteConfigs(bool $dryRun): array
    {
        $db = $this->db();
        $map = [];
        foreach ($db->table('categories')->get(['id', 'central_category_id']) as $row) {
            $map[(int) $row->id] = $row->central_category_id !== null ? (string) CatalogRef::central((int) $row->central_category_id) : (string) CatalogRef::tenant((int) $row->id);
        }

        $result = ['rewritten' => 0, 'unknown' => [], 'changes' => [], 'pending' => 0];
        foreach (self::CONFIGS as [$table, $key, $column, $kind]) {
            if (! CatalogRefColumns::has($db, $table, $column)) {
                continue;
            }
            foreach ($db->table($table)->whereNotNull($column)->where($column, 'like', '%ategor%')->get([$key, $column]) as $row) {
                $unknown = [];
                $count = 0;
                $old = (string) $row->{$column};
                $new = $this->rewriteValue($old, $kind, $map, $unknown, $count);
                foreach ($unknown as $id) {
                    $result['unknown'][] = ['table' => $table, 'column' => $column, 'row' => $row->{$key}, 'category_id' => $id];
                }
                if ($count === 0 || $new === $old) {
                    continue;
                }
                $result['rewritten'] += $count;
                $result['changes'][] = ['table' => $table, 'column' => $column, 'row' => $row->{$key}, 'old' => $old, 'new' => $new];
                if (! $dryRun) {
                    // Compare-and-swap (a config edited meanwhile is left for the next run); JSON columns compare as JSON.
                    $db->table($table)->where($key, $row->{$key})
                        ->when($kind === 'json', fn ($q) => $q->whereRaw("`{$column}` = CAST(? AS JSON)", [$old]), fn ($q) => $q->where($column, $old))
                        ->update([$column => $new]);
                }
            }
        }

        return $result;
    }

    /** @param  array<int, string>  $map  tenant category id → ref */
    private function rewriteValue(string $value, string $kind, array $map, array &$unknown, int &$count): string
    {
        if ($kind === 'json' || $kind === 'text') {
            $decoded = json_decode($value);
            if (is_array($decoded) || is_object($decoded)) {
                $walked = $this->walk($decoded, $map, $unknown, $count);

                return $count > 0 ? (string) json_encode($walked, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION) : $value;
            }
        }

        return $this->rewriteUrl($value, $map, $unknown, $count);
    }

    private function walk(mixed $node, array $map, array &$unknown, int &$count, ?string $key = null): mixed
    {
        if ($node instanceof \stdClass) {
            $out = new \stdClass();
            foreach (get_object_vars($node) as $k => $v) {
                $out->{$k} = $this->walk($v, $map, $unknown, $count, (string) $k);
            }

            return $out;
        }

        if (is_array($node)) {
            $isIdKey = $key !== null && in_array(strtolower($key), self::ID_KEYS, true);
            $out = [];
            foreach ($node as $k => $v) {
                $out[$k] = $isIdKey && array_is_list($node) ? $this->mapId($v, $map, $unknown, $count) : $this->walk($v, $map, $unknown, $count, is_string($k) ? $k : null);
            }

            return $out;
        }

        if ($key !== null && in_array(strtolower($key), self::ID_KEYS, true)) {
            return $this->mapId($node, $map, $unknown, $count);
        }

        return is_string($node) && str_contains($node, 'category_id=') ? $this->rewriteUrl($node, $map, $unknown, $count) : $node;
    }

    private function mapId(mixed $value, array $map, array &$unknown, int &$count): mixed
    {
        if (! (is_int($value) || (is_string($value) && preg_match('/^[1-9][0-9]*$/', $value)))) {
            return $value; // already a ref / empty / something else: untouched
        }
        $id = (int) $value;
        if (! isset($map[$id])) {
            $unknown[] = $id;

            return $value; // unknown ids are kept, never dropped
        }
        $count++;

        return $map[$id];
    }

    private function rewriteUrl(string $value, array $map, array &$unknown, int &$count): string
    {
        return (string) preg_replace_callback('/([?&]category_id=)([1-9][0-9]*)(?=&|#|$)/', function (array $m) use ($map, &$unknown, &$count) {
            $id = (int) $m[2];
            if (! isset($map[$id])) {
                $unknown[] = $id;

                return $m[0];
            }
            $count++;

            return $m[1] . rawurlencode($map[$id]);
        }, $value);
    }

    // ── verify ──────────────────────────────────────────────────────────────

    public function verify(Tenant $tenant): array
    {
        CategoryCatalog::flushMemo();

        $old = [];
        foreach (Category::query()->with(['translations.language', 'files', 'centralCategory.files', 'parent'])->get() as $category) {
            $key = $category->central_category_id !== null ? (int) $category->central_category_id : (int) $category->id + CategoryKey::OWN_OFFSET;
            $parent = $category->parent_id === null ? null : Category::withoutGlobalScope(Category::CENTRAL_VISIBLE_SCOPE)->find($category->parent_id);
            $parentKey = $parent === null ? null : ($parent->central_category_id !== null ? (int) $parent->central_category_id : (int) $parent->id + CategoryKey::OWN_OFFSET);
            $old[$key] = $this->tuple($parentKey, $category);
        }

        $new = [];
        foreach (MergedCategory::query()->with(['translations.language', 'files', 'centralCategory.files'])->get() as $category) {
            $new[(int) $category->id] = $this->tuple($category->parent_id === null ? null : (int) $category->parent_id, $category);
        }

        $categoryDiffs = [];
        foreach (array_unique(array_merge(array_keys($old), array_keys($new))) as $key) {
            if (($old[$key] ?? null) !== ($new[$key] ?? null)) {
                $categoryDiffs[] = ['key' => $key, 'old' => $old[$key] ?? null, 'new' => $new[$key] ?? null];
            }
        }

        // Membership multiset (category key, tenant product id, sort) over the categories each read shows.
        $db = $this->db();
        $oldPairs = [];
        foreach ($db->select(
            'SELECT IFNULL(k.`central_category_id`, k.`id` + ' . CategoryKey::OWN_OFFSET . ') AS ck, t.`product_id`, t.`sort_order`
               FROM `category_product` t JOIN `categories` k ON k.`id` = t.`category_id` JOIN `products` p ON p.`id` = t.`product_id`'
            . (CatalogRefColumns::has($db, 'categories', 'central_visible') ? ' WHERE k.`central_visible` = 1' : '')
        ) as $r) {
            $k = $r->ck . '|' . $r->product_id . '|' . (int) $r->sort_order;
            $oldPairs[$k] = ($oldPairs[$k] ?? 0) + 1;
        }
        $newPairs = [];
        foreach ($db->select('SELECT category_key, product_id, sort_order FROM (' . CategoryCatalog::membershipSql($db) . ') m') as $r) {
            $k = $r->category_key . '|' . $r->product_id . '|' . (int) $r->sort_order;
            $newPairs[$k] = ($newPairs[$k] ?? 0) + 1;
        }
        $onlyOld = $onlyNew = [];
        foreach ($oldPairs as $k => $n) {
            if (($d = $n - ($newPairs[$k] ?? 0)) > 0) {
                $onlyOld[$k] = $d;
            }
        }
        foreach ($newPairs as $k => $n) {
            if (($d = $n - ($oldPairs[$k] ?? 0)) > 0) {
                $onlyNew[$k] = $d;
            }
        }

        $config = $this->rewriteConfigs(true); // dry: numeric ids still mappable = not migrated yet

        return [
            'diffs' => count($categoryDiffs) + array_sum($onlyOld) + array_sum($onlyNew) + $config['rewritten'],
            'counts' => [
                'old_categories' => count($old), 'new_categories' => count($new), 'category_diffs' => count($categoryDiffs),
                'old_pairs' => array_sum($oldPairs), 'new_pairs' => array_sum($newPairs),
                'only_old' => array_sum($onlyOld), 'only_new' => array_sum($onlyNew),
                'config_ids_pending' => $config['rewritten'], 'config_unknown_ids' => count($config['unknown']),
            ],
            'category_diffs' => array_slice($categoryDiffs, 0, self::EXAMPLES),
            'only_old' => array_slice(array_keys($onlyOld), 0, self::EXAMPLES),
            'only_new' => array_slice(array_keys($onlyNew), 0, self::EXAMPLES),
            'note' => 'Category / pair drift of the copy (stale sync) is a diff: run `tenants:sync-data --section=categories --section=products` for this tenant, then migrate + verify again.',
        ];
    }

    /** @return array<string, mixed> comparable view of one category (as the storefront / panel read it) */
    private function tuple(?int $parentKey, Category $category): array
    {
        $texts = [];
        foreach ($category->translations as $translation) {
            $code = $translation->language?->code;
            if ($code !== null && in_array($translation->field, ['name', 'slug', 'description', 'meta_keywords', 'meta_description'], true)) {
                $texts[$code . '.' . $translation->field] ??= trim((string) $translation->value);
            }
        }
        ksort($texts);

        return [
            'parent' => $parentKey,
            'active' => (bool) $category->active,
            'featured' => (bool) $category->featured,
            'order_number' => (int) $category->order_number,
            'texts' => $texts,
            'thumb' => $category->thumb_url,
        ];
    }
}
