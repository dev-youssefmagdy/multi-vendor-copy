<?php

namespace App\Jobs;

use App\Services\NeozenaCatalogImporter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ImportNeozenaProductJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 600;

    public function __construct(public ?int $externalId, public $data = null)
    {
        $this->onQueue('neozena-import');
    }

    public function handle(NeozenaCatalogImporter $importer): void
    {
        if(!$this->externalId) {
            $data = $this->data;
        }else{
            $data = $importer->fetchProduct($this->externalId);
        }

        if (!$data) {
            info('Neozena product not fetched', ['external_id' => $this->externalId]);

            return;
        }

        $importer->importProduct($data);
    }
}
