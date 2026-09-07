<?php

namespace App\Jobs\Tenant;

use App\Models\Tenant as TenantModel;
use App\Models\Tenant\Translation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpsertTranslationChunkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(
        public string $tenantId,
        public array $rows,
    ) {
        $this->onQueue('translations');
    }

    public function handle(): void
    {
        $tenant = TenantModel::find($this->tenantId);

        if (!$tenant) {
            return;
        }

        tenancy()->initialize($tenant);

        try {
            Translation::query()->upsert(
                $this->rows,
                ['language_id', 'translatable_type', 'translatable_id', 'field'],
                ['value', 'updated_at'],
            );
        } finally {
            tenancy()->end();
        }
    }
}
