<?php

namespace App\Jobs;

use App\Services\NeozenaCatalogImporter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ImportNeozenaPageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;

    public function __construct(
        public int $page,
        public bool $continue = true,
    ) {
        $this->onQueue('neozena-import');
    }

    public function handle(NeozenaCatalogImporter $importer): void
    {
        $payload = $importer->fetchPage($this->page);

        if (!$payload) {
            Log::warning('Neozena page returned no payload', ['page' => $this->page]);

            return;
        }

        foreach ($payload['data'] ?? [] as $product) {
            if (!isset($product['id'])) {
                continue;
            }
            info('Queueing import for Neozena product', ['external_id' => $product['id']]);
            ImportNeozenaProductJob::dispatch((int) $product['id']);
        }

        if (!$this->continue) {
            return;
        }

        $lastPage = (int) data_get($payload, 'meta.last_page', 1);
        $nextUrl = data_get($payload, 'links.next');

        if ($nextUrl && $this->page < $lastPage) {
            ImportNeozenaPageJob::dispatch($this->page + 1, true);
        }
    }
}
