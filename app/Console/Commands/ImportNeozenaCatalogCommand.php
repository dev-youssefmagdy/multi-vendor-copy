<?php

namespace App\Console\Commands;

use App\Jobs\ImportNeozenaPageJob;
use App\Jobs\ImportNeozenaProductJob;
use App\Services\NeozenaCatalogImporter;
use Illuminate\Console\Command;

class ImportNeozenaCatalogCommand extends Command
{
    protected $signature = 'neozena:import
        {--page=1 : Starting page number}
        {--only-page : Import only the given page (do not chain through next pages)}
        {--product= : Import a single product by its external id (skips page listing)}
        {--categories : Import categories from /api/catalog/categories (requires --sync)}
        {--variants : Import variant groups from /api/catalog/variants (requires --sync)}
        {--sync : Run synchronously instead of dispatching to the queue}';

    protected $description = 'Import products, categories and related data from the Neozena catalog API into the local database via queue jobs.';

    public function handle(NeozenaCatalogImporter $importer): int
    {
        if ($productId = $this->option('product')) {
            $externalId = (int) $productId;
            if ($this->option('sync')) {
                $data = $importer->fetchProduct($externalId);
                if ($data) {
                    $importer->importProduct($data);
                    $this->info("Imported product {$externalId} synchronously.");
                } else {
                    $this->error("Failed to fetch product {$externalId}.");

                    return self::FAILURE;
                }
            } else {
                ImportNeozenaProductJob::dispatch($externalId);
                $this->info("Queued ImportNeozenaProductJob for external id {$externalId}.");
            }

            return self::SUCCESS;
        }

        $page = (int) $this->option('page');
        $continue = !$this->option('only-page');

        if ($this->option('sync')) {
            $this->runSynchronously($importer, $page, $continue);

            return self::SUCCESS;
        }

        if ($this->option('categories') || $this->option('variants')) {
            $this->warn('--categories and --variants require --sync. Re-run with the --sync flag.');

            return self::FAILURE;
        }

        ImportNeozenaPageJob::dispatch($page, $continue);
        $this->info(sprintf(
            'Queued ImportNeozenaPageJob starting from page %d (continue=%s) on the "neozena-import" queue.',
            $page,
            $continue ? 'yes' : 'no'
        ));
        $this->line('Run a worker with: php artisan queue:work --queue=neozena-import');

        return self::SUCCESS;
    }

    protected function runSynchronously(NeozenaCatalogImporter $importer, int $page, bool $continue): void
    {
        if ($this->option('categories')) {
            $this->runCategoriesSynchronously($importer, $page, $continue);

            return;
        }

        if ($this->option('variants')) {
            $this->runVariantsSynchronously($importer, $page, $continue);

            return;
        }

        // Full sync: categories → variants → products (in dependency order).
        $this->info('Step 1/3: Importing categories...');
        $this->runCategoriesSynchronously($importer, 1, true);

        $this->info('Step 2/3: Importing variant groups...');
        $this->runVariantsSynchronously($importer, 1, true);

        $this->info('Step 3/3: Importing products...');
        ImportNeozenaPageJob::dispatch($page, true)->onQueue('neozena-import');
        //        do {
//            $payload = $importer->fetchPage($page);
//            if (!$payload) {
//                $this->error("Failed to fetch page {$page}.");
//
//                return;
//            }
//
//            $this->info(sprintf('Page %d / %d', $page, (int) data_get($payload, 'meta.last_page', $page)));
//
//            foreach ($payload['data'] ?? [] as $product) {
//                if (!isset($product['id'])) {
//                    continue;
//                }
//                $detail = $importer->fetchProduct((int) $product['id']);
//                if ($detail) {
//                    $importer->importProduct($detail);
//                    $this->line(' - imported ' . ($detail['sku'] ?? $detail['id']));
//                }
//            }
//
//            if (!$continue) {
//                return;
//            }
//
//            $hasNext = (bool) data_get($payload, 'links.next');
//            $lastPage = (int) data_get($payload, 'meta.last_page', $page);
//            $page++;
//        } while ($hasNext && $page <= $lastPage);
    }

    protected function runCategoriesSynchronously(NeozenaCatalogImporter $importer, int $page, bool $continue): void
    {
        do {
            $payload = $importer->fetchCategoriesPage($page);
            if (!$payload) {
                $this->error("Failed to fetch categories page {$page}.");

                return;
            }

            $this->info(sprintf('Categories page %d / %d', $page, (int) data_get($payload, 'meta.last_page', $page)));

            foreach ($payload['data'] ?? [] as $categoryData) {
                if (!isset($categoryData['id'])) {
                    continue;
                }
                $category = $importer->importCategory($categoryData);
                if ($category) {
                    $this->line(' - category ' . $categoryData['id'] . ': ' . ($categoryData['name'] ?? ''));
                }
            }

            if (!$continue) {
                return;
            }

            $hasNext = (bool) data_get($payload, 'links.next');
            $lastPage = (int) data_get($payload, 'meta.last_page', $page);
            $page++;
        } while ($hasNext && $page <= $lastPage);
    }

    protected function runVariantsSynchronously(NeozenaCatalogImporter $importer, int $page, bool $continue): void
    {
        do {
            $payload = $importer->fetchVariantsPage($page);
            if (!$payload) {
                $this->error("Failed to fetch variants page {$page}.");

                return;
            }

            $this->info(sprintf('Variants page %d / %d', $page, (int) data_get($payload, 'meta.last_page', $page)));

            foreach ($payload['data'] ?? [] as $variantData) {
                if (!isset($variantData['id'])) {
                    continue;
                }
                $importer->importVariantGroup($variantData);
                $this->line(' - variant ' . $variantData['id'] . ': ' . ($variantData['name'] ?? '') . ' (' . count($variantData['options'] ?? []) . ' options)');
            }

            if (!$continue) {
                return;
            }

            $hasNext = (bool) data_get($payload, 'links.next');
            $lastPage = (int) data_get($payload, 'meta.last_page', $page);
            $page++;
        } while ($hasNext && $page <= $lastPage);
    }
}

