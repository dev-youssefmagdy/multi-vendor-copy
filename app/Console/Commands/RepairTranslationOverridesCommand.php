<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\Tenant\Language;
use App\Models\Tenant\TranslationOverride;
use App\Translation\TenantTranslator;
use Illuminate\Console\Command;

/**
 * Finds tenant translation overrides that are blank or merely repeat the
 * English source for a non-English language. Report-only unless --apply is
 * given; deleting a row makes the key fall back to the lang files.
 */
class RepairTranslationOverridesCommand extends Command
{
    protected $signature = 'translations:repair-overrides
        {--tenant=* : Limit to these tenant ids}
        {--apply : Delete the rows instead of only reporting them}';

    protected $description = 'Report (or, with --apply, delete) tenant translation overrides that hold no real translation';

    public function handle(): int
    {
        $tenants = Tenant::query()
            ->when($this->option('tenant'), fn ($query, $ids) => $query->whereIn('id', $ids))
            ->get();

        $apply = (bool) $this->option('apply');
        $found = 0;

        foreach ($tenants as $tenant) {
            tenancy()->initialize($tenant);

            foreach (Language::query()->get() as $language) {
                $found += $this->repairLanguage((string) $tenant->id, $language, $apply);
            }

            tenancy()->end();
        }

        $this->info(($apply ? 'Deleted ' : 'Found ') . "{$found} override(s) holding no real translation." . ($apply ? '' : ' Re-run with --apply to delete them.'));

        return self::SUCCESS;
    }

    private function repairLanguage(string $tenantId, Language $language, bool $apply): int
    {
        $translator = app('translator');
        $locale = strtolower((string) $language->code);
        $badIds = [];
        $samples = [];

        TranslationOverride::query()
            ->where('language_id', $language->id)
            ->select(['id', 'key', 'value'])
            ->cursor()
            ->each(function (TranslationOverride $row) use ($translator, $locale, &$badIds, &$samples) {
                if ($translator->isUntranslatedValue((string) $row->key, (string) $row->value, $locale)) {
                    $badIds[] = $row->id;
                    if (count($samples) < 3) {
                        $samples[] = (string) $row->key;
                    }
                }
            });

        if ($badIds === []) {
            return 0;
        }

        $this->line("{$tenantId} [{$locale}]: " . count($badIds) . ' e.g. ' . implode(' | ', $samples));

        if ($apply) {
            foreach (array_chunk($badIds, 500) as $chunk) {
                TranslationOverride::query()->whereKey($chunk)->delete();
            }

            TenantTranslator::flushCache();
        }

        return count($badIds);
    }
}
