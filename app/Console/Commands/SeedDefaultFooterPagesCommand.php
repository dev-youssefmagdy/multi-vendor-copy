<?php

namespace App\Console\Commands;

use App\Services\StaticPageService;
use Illuminate\Console\Command;

class SeedDefaultFooterPagesCommand extends Command
{
    protected $signature = 'pages:seed-footer-defaults';
    protected $description = 'Create the default central static pages referenced by the storefront theme footers, then sync them into every tenant';

    public function handle(StaticPageService $service): int
    {
        $created = $service->seedDefaultFooterPages();

        foreach ($created as $slug) {
            $this->line("+ {$slug}");
        }

        $this->info(count($created) . ' central static page(s) created.');

        $this->call('tenants:sync-data', [
            '--all' => true,
            '--section' => ['pages'],
        ]);

        return self::SUCCESS;
    }
}
