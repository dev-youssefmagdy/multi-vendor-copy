<?php

namespace App\Jobs;

use App\Models\Package;
use App\Models\PaymentLog;
use App\Models\Tenant;
use App\Models\Tenant\Transaction;
use App\Services\Mail\TemplateMailService;
use App\Services\WebsiteRegistrationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/** Welcome / subscription mails, admin notice and affiliate commissions — off the registration request. */
class SendTenantRegistrationNotificationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(
        public string $tenantId,
        public string $storeUrl,
        public string $adminLoginUrl,
        public ?string $subdomainUrl,
        public ?string $locale,
        public ?int $packageId,
        public ?int $paymentLogId,
        public ?array $payment,
        public ?int $walletTransactionId = null,
    ) {
    }

    public function handle(WebsiteRegistrationService $service, TemplateMailService $mail): void
    {
        $tenant = Tenant::query()->with(['domains', 'package.translations.language'])->find($this->tenantId);

        if (!$tenant) {
            return;
        }

        $package = $this->packageId ? Package::query()->find($this->packageId) : null;
        $paymentLog = $this->paymentLogId ? PaymentLog::query()->find($this->paymentLogId) : null;

        if ($package && $paymentLog && $this->payment) {
            $service->notifySubscriptionActivated($tenant, $package, $paymentLog, $this->payment, $this->locale);
        }

        $service->notifyTenantRegistered($tenant, $this->storeUrl, $this->adminLoginUrl, $this->subdomainUrl, $this->locale);

        if ($this->walletTransactionId) {
            $this->sendWalletReceipts($tenant, $mail);
        }
    }

    /** What TenantTransactionObserver does on creation — provisioning writes the transaction without it. */
    protected function sendWalletReceipts(Tenant $tenant, TemplateMailService $mail): void
    {
        tenancy()->initialize($tenant);

        try {
            $transaction = Transaction::query()->find($this->walletTransactionId);

            if ($transaction) {
                $mail->sendTenantWalletTransactionReceipt($transaction, $tenant);
                $mail->sendAdminVendorWalletTransaction($tenant, $transaction);
            }
        } finally {
            tenancy()->end();
        }
    }
}
