<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class HtmlTemplateMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $subjectLine,
        public string $htmlBody,
        public array $binaryAttachments = [],
    ) {
    }

    public function build(): self
    {
        $message = $this->subject($this->subjectLine)
            ->view('emails.order-invoice')
            ->with([
                'htmlBody' => $this->htmlBody,
            ]);

        foreach ($this->binaryAttachments as $attachment) {
            $message->attachData(
                $attachment['data'],
                $attachment['name'],
                $attachment['options'] ?? []
            );
        }

        return $message;
    }
}
