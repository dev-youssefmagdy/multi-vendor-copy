<?php

namespace App\Mail;

use App\Models\TenantPayout;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TenantPayoutMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly TenantPayout $payout)
    {
    }

    public function build(): self
    {
        return $this
            ->subject("Payment Received from Platform – Invoice {$this->payout->invoice_number}")
            ->view('emails.tenant-payout');
    }
}
