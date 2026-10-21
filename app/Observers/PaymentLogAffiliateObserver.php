<?php

namespace App\Observers;

use App\Enums\PaymentLogStatus;
use App\Models\PaymentLog;
use App\Services\AffiliateService;

class PaymentLogAffiliateObserver
{
    /** A refunded subscription payment claws back its affiliate commission. */
    public function updated(PaymentLog $paymentLog): void
    {
        if ($paymentLog->wasChanged('status') && $paymentLog->status === PaymentLogStatus::Refunded) {
            app(AffiliateService::class)->reverseForPaymentLog($paymentLog);
        }
    }
}
