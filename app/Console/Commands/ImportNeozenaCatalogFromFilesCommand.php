<?php

namespace App\Console\Commands;

use App\Jobs\ImportNeozenaProductJob;
use App\Services\NeozenaCatalogImporter;
use Illuminate\Console\Command;

class ImportNeozenaCatalogFromFilesCommand extends Command
{
    protected $signature = 'neozena:import-files
        {--product= : Import a single product by its external id}
        {--categories : Import only categories from catalog/categories.json}
        {--variants : Import only variant groups from catalog/variants.json}
        {--dir=catalog : Directory containing the JSON files (relative to base path)}';

    protected $description = 'Import products, categories and variant groups from local ./catalog/*.json files into the local database.';

    public function handle(NeozenaCatalogImporter $importer): int
    {
        $dir = rtrim(base_path($this->option('dir')), '/');

        if ($productId = $this->option('product')) {
            return $this->importSingleProduct($importer, $dir, (int) $productId);
        }

        if ($this->option('categories')) {
            $this->importCategories($importer, $dir);

            return self::SUCCESS;
        }

        if ($this->option('variants')) {
            $this->importVariants($importer, $dir);

            return self::SUCCESS;
        }

        // Full import: categories → variants → products
        $this->info('Step 1/3: Importing categories...');
        $this->importCategories($importer, $dir);

        $this->info('Step 2/3: Importing variant groups...');
        $this->importVariants($importer, $dir);

        $this->info('Step 3/3: Importing products...');
        $this->importProducts($importer, $dir);

        return self::SUCCESS;
    }

    protected function importCategories(NeozenaCatalogImporter $importer, string $dir): void
    {
        $items = $this->loadJson($dir . '/categories.json');
        if ($items === null) {
            return;
        }

        $this->info(sprintf('Importing %d categories...', count($items)));

        foreach ($items as $data) {
            if (!isset($data['id'])) {
                continue;
            }
            $importer->importCategory($data);
            $this->line(' - category ' . $data['id'] . ': ' . ($data['name'] ?? ''));
        }
    }

    protected function importVariants(NeozenaCatalogImporter $importer, string $dir): void
    {
        $items = $this->loadJson($dir . '/variants.json');
        if ($items === null) {
            return;
        }

        $this->info(sprintf('Importing %d variant groups...', count($items)));

        foreach ($items as $data) {
            if (!isset($data['id'])) {
                continue;
            }
            $importer->importVariantGroup($data);
            $this->line(' - variant ' . $data['id'] . ': ' . ($data['name'] ?? '') . ' (' . count($data['options'] ?? []) . ' options)');
        }
    }

    protected function importProducts(NeozenaCatalogImporter $importer, string $dir): void
    {
        $items = $this->loadJson($dir . '/products.json');
        if ($items === null) {
            return;
        }

        $this->info(sprintf('Importing %d products...', count($items)));

        foreach ($items as $data) {
            if (!isset($data['id'])) {
                continue;
            }
            ImportNeozenaProductJob::dispatch(null, $data);

            // $importer->importProduct($data);
            $this->line(' - imported ' . ($data['sku'] ?? $data['id']));
        }
    }

    protected function importSingleProduct(NeozenaCatalogImporter $importer, string $dir, int $externalId): int
    {
        $items = $this->loadJson($dir . '/products.json');
        if ($items === null) {
            return self::FAILURE;
        }

        $data = collect($items)->firstWhere('id', $externalId);

        if (!$data) {
            $this->error("Product with external id {$externalId} not found in products.json.");

            return self::FAILURE;
        }

        $importer->importProduct($data);
        $this->info("Imported product {$externalId}.");

        return self::SUCCESS;
    }

    protected function loadJson(string $path): ?array
    {
        if (!file_exists($path)) {
            $this->error("File not found: {$path}");

            return null;
        }

        $decoded = json_decode(file_get_contents($path), true);

        if (!is_array($decoded)) {
            $this->error("Invalid JSON or unexpected structure in: {$path}");

            return null;
        }

        return $decoded;
    }
}
