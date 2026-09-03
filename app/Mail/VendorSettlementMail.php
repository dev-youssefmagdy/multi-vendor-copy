<?php

namespace App\Mail;

use App\Models\VendorSettlement;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VendorSettlementMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly VendorSettlement $settlement,
        public readonly array $breakdown,
    ) {
    }

    public function build(): self
    {
        return $this
            ->subject("Settlement Invoice #{$this->settlement->invoice_number} – Payment Confirmed")
            ->view('emails.vendor-settlement');
    }
}
