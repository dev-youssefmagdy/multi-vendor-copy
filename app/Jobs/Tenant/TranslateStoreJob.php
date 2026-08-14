<?php

namespace App\Jobs\Tenant;

use App\Models\Tenant as TenantModel;
use App\Models\Tenant\Language;
use App\Services\Tenant\StoreTranslatorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TranslateStoreJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    /** Allow up to 1 hour — full-store translation makes many sequential OpenAI calls. */
    public int $timeout = 3600;

    public function __construct(
        public string $tenantId,
        public int $languageId,
        public ?string $sourceLocale = null,
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
            $language = Language::query()->find($this->languageId);

            if (!$language) {
                return;
            }

            app(StoreTranslatorService::class)->translateStore($language, $this->sourceLocale);
        } catch (\Throwable $e) {
            Log::error("TranslateStoreJob: failed for tenant [{$this->tenantId}] language [{$this->languageId}]: " . $e->getMessage());
            throw $e;
        } finally {
            tenancy()->end();
        }
    }
}
