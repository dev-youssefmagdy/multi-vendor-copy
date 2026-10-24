<?php

namespace App\Jobs;

use App\Models\Package;
use App\Models\PaymentLog;
use App\Models\Tenant;
use App\Services\StaticPageService;
use App\Services\Tenant\TenantCatalogSyncService;
use App\Services\Tenant\TenantDatabasePool;
use App\Services\TenantService;
use App\Services\WebsiteRegistrationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Stancl\Tenancy\Database\DatabaseManager;
use Stancl\Tenancy\Exceptions\TenantDatabaseAlreadyExistsException;
use Stancl\Tenancy\Jobs\CreateDatabase;
use Stancl\Tenancy\Jobs\MigrateDatabase;
use Throwable;

/**
 * Builds a freshly registered tenant in the background so the registration request can return at once.
 *
 * Phase 1 (this job, `tenant-provision`): tenant DB (claimed from the pre-migrated pool, else created + migrated),
 * the data the onboarding wizard needs (languages, themes, ...), owner admin and billing records. Marks `provision_status=ready`.
 * Phase 2 (`tenant-sync` chain): catalog (categories, products, ...). Marks `sync_status=ready`.
 *
 * Encrypted because the payload carries the owner's registration password. Idempotent: safe to retry.
 */
class ProvisionTenantJob implements ShouldQueue, ShouldBeEncrypted
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Needed by wizard steps 2/3 — synced before the wizard is unlocked. */
    public const CORE_SECTIONS = ['languages', 'currencies', 'settings', 'payment-gateways', 'themes'];

    public int $tries = 2;
    public int $timeout = 900;

    public function __construct(
        public string $tenantId,
        public array $ownerAttributes,
        public ?array $payment,
        public ?int $paymentLogId,
        public string $storeUrl,
        public string $adminLoginUrl,
        public ?string $subdomainUrl,
        public ?string $locale,
    ) {
        $this->onQueue('tenant-provision');
    }

    public function handle(
        TenantService $tenantService,
        WebsiteRegistrationService $registrationService,
        TenantCatalogSyncService $syncService,
        StaticPageService $staticPageService,
    ): void {
        $tenant = Tenant::query()->find($this->tenantId);

        if (!$tenant) {
            return;
        }

        $this->createDatabase($tenant);

        // Languages first: the owner admin's appearance defaults (banners) map translations onto them.
        $syncService->syncSectionsAtomically($tenant, self::CORE_SECTIONS);
        tenancy()->end();

        $tenantService->syncTenantOwnerAdmin($tenant, $this->ownerAttributes);

        $package = filled($this->ownerAttributes['package_id'] ?? null)
            ? Package::query()->find((int) $this->ownerAttributes['package_id'])
            : null;
        $paymentLog = $this->paymentLogId ? PaymentLog::query()->find($this->paymentLogId) : null;

        $walletTransactionId = null;

        if ($package && $this->payment && !$this->billingExists($tenant)) {
            // Receipts are mailed by the notifications job below, not inline (SMTP is seconds per mail).
            $walletTransactionId = $registrationService->createTenantBillingRecords($tenant, $package, $this->payment, deferReceipts: true);
        }

        // Footer pages must exist centrally before the `pages` section is pulled into the tenant.
        $staticPageService->seedDefaultFooterPages();

        Tenant::saveData($this->tenantId, ['provision_status' => 'ready']);

        SendTenantRegistrationNotificationsJob::dispatch(
            $this->tenantId,
            $this->storeUrl,
            $this->adminLoginUrl,
            $this->subdomainUrl,
            $this->locale,
            $package?->id,
            $paymentLog?->id,
            $this->payment,
            $walletTransactionId,
        );

        $this->dispatchCatalogSync();
    }

    public function failed(Throwable $e): void
    {
        report($e);

        Tenant::saveData($this->tenantId, ['provision_status' => 'failed']);
    }

    protected function createDatabase(Tenant $tenant): void
    {
        $pool = app(TenantDatabasePool::class);
        $pooled = $pool->enabled() ? $pool->claim($tenant) : null;

        if ($pooled) {
            RefillTenantDatabasePoolJob::dispatch();

            // A database prepared before the latest tenant migration only needs the newer ones applied.
            if (!$pooled['current']) {
                (new MigrateDatabase($tenant))->handle();
            }

            return;
        }

        try {
            (new CreateDatabase($tenant))->handle(app(DatabaseManager::class));
        } catch (TenantDatabaseAlreadyExistsException) {
            // Retry after a partial run — migrations below are idempotent.
        }

        (new MigrateDatabase($tenant))->handle();
    }

    protected function billingExists(Tenant $tenant): bool
    {
        tenancy()->initialize($tenant);

        try {
            return \App\Models\Tenant\Subscription::query()->exists();
        } finally {
            tenancy()->end();
        }
    }

    /** Catalog sections synced after provisioning (phase 2), in order — categories before products. */
    public static function catalogSections(): array
    {
        return array_values(array_diff(app(TenantCatalogSyncService::class)->sections(), self::CORE_SECTIONS));
    }

    /**
     * Remaining sections run in order while the user fills the wizard. Profit is applied inside the chain,
     * right after products, so `sync_status=ready` (which unlocks Launch) means prices are final too.
     */
    protected function dispatchCatalogSync(): void
    {
        $tenantId = $this->tenantId;
        $jobs = [];

        foreach (self::catalogSections() as $section) {
            $jobs[] = new SyncTenantSectionJob($tenantId, $section, onboarding: true);

            if ($section === 'products') {
                $jobs[] = (new ApplyTenantProfitPercentageJob($tenantId))->onQueue('tenant-sync');
            }
        }

        $jobs[] = new MarkTenantSyncReadyJob($tenantId);

        Bus::chain($jobs)
            ->onQueue('tenant-sync')
            ->catch(function (Throwable $e) use ($tenantId) {
                report($e);
                Tenant::saveData($tenantId, ['sync_status' => 'failed']);
            })
            ->dispatch();
    }
}
