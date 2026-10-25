<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Models\Tenant\Translation;
use App\Observers\CacheVersionObserver;
use App\Support\CacheVersion;
use Illuminate\Support\Facades\DB;

/**
 * Set-based replacement for the per-model Translator::syncTranslations()
 * (DELETE all + one INSERT per model).
 *
 * For a batch of translatable models it reads their current rows in one query,
 * diffs them against the desired rows and only writes the difference:
 * one multi-row upsert for new/changed rows and one DELETE for stale rows.
 * The end state is identical to "delete every translation of each model, then
 * insert the desired ones" but unchanged rows are never touched.
 *
 * Runs on the active (tenant) connection.
 */
final class BulkTranslationWriter
{
    private const WRITE_CHUNK = 500;

    private const READ_CHUNK = 1000;

    /**
     * Current translations of the given models.
     *
     * @param  int[]  $modelIds
     * @return array<int, array<int, array<string, string>>> modelId => languageId => field => value
     */
    public function existing(string $type, array $modelIds): array
    {
        $existing = [];

        foreach (array_chunk($modelIds, self::READ_CHUNK) as $ids) {
            $rows = Translation::query()->toBase()
                ->where('translatable_type', $type)
                ->whereIn('translatable_id', $ids)
                ->get(['translatable_id', 'language_id', 'field', 'value']);

            foreach ($rows as $row) {
                $existing[(int) $row->translatable_id][(int) $row->language_id][$row->field] = $row->value;
            }
        }

        return $existing;
    }

    /**
     * Make the translations of every model in $desired match exactly.
     * Models that are not keys of $desired are left untouched.
     *
     * @param  array<int, array<int, array<string, string>>>  $desired  modelId => languageId => field => value
     * @param  array<int, array<int, array<string, string>>>|null  $existing  result of existing() when already loaded
     * @return array{written: int, deleted: int}
     */
    public function replace(string $type, array $desired, ?array $existing = null): array
    {
        if ($desired === []) {
            return ['written' => 0, 'deleted' => 0];
        }

        $existing ??= $this->existing($type, array_keys($desired));
        $now = now();
        $upserts = [];
        $stale = [];

        foreach ($desired as $modelId => $languages) {
            foreach ($languages as $languageId => $fields) {
                foreach ($fields as $field => $value) {
                    if (($existing[$modelId][$languageId][$field] ?? null) === $value) {
                        continue;
                    }

                    $upserts[] = [
                        'language_id' => $languageId,
                        'translatable_type' => $type,
                        'translatable_id' => $modelId,
                        'field' => $field,
                        'value' => $value,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            foreach ($existing[$modelId] ?? [] as $languageId => $fields) {
                foreach (array_keys($fields) as $field) {
                    if (! isset($desired[$modelId][$languageId][$field])) {
                        $stale[] = [$modelId, $languageId, $field];
                    }
                }
            }
        }

        $deleted = 0;
        foreach (array_chunk($stale, self::WRITE_CHUNK) as $chunk) {
            $deleted += $this->deleteRows($type, $chunk);
        }

        // The unique key (language_id, translatable_type, translatable_id, field) turns
        // this into insert-or-update-value; created_at is only set by the insert branch.
        foreach (array_chunk($upserts, self::WRITE_CHUNK) as $chunk) {
            Translation::query()->toBase()->upsert(
                $chunk,
                ['language_id', 'translatable_type', 'translatable_id', 'field'],
                ['value', 'updated_at'],
            );
        }

        // Raw upserts/deletes skip the Translation observer.
        if ($upserts !== [] || $deleted > 0) {
            CacheVersion::bumpMany(array_filter(['Translation', CacheVersionObserver::ownerTag($type)]));
        }

        return ['written' => count($upserts), 'deleted' => $deleted];
    }

    /**
     * @param  array<int, array{0: int, 1: int, 2: string}>  $rows  [modelId, languageId, field]
     */
    private function deleteRows(string $type, array $rows): int
    {
        $placeholders = implode(',', array_fill(0, count($rows), '(?,?,?)'));
        $bindings = [$type];

        foreach ($rows as [$modelId, $languageId, $field]) {
            array_push($bindings, $modelId, $languageId, $field);
        }

        return DB::delete(
            "delete from translations where translatable_type = ? and (translatable_id, language_id, field) in ({$placeholders})",
            $bindings,
        );
    }
}
