<?php

namespace App\Mail;

use App\Models\VendorSettlement;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VendorSettlementAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly VendorSettlement $settlement,
        public readonly string $settlementsUrl,
    ) {
    }

    public function build(): self
    {
        return $this
            ->subject("New Vendor Settlement Received – {$this->settlement->invoice_number}")
            ->view('emails.vendor-settlement-admin');
    }
}
