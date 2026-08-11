<?php

namespace App\Livewire\Website;

use App\Mail\HtmlTemplateMail;
use App\Repositories\AppSettingRepository;
use Illuminate\Support\Facades\Mail;
use Throwable;
use Livewire\Component;

class ContactPage extends Component
{
    public string $contactName = '';
    public string $contactEmail = '';
    public string $contactSubject = '';
    public string $contactMessage = '';

    public string $supportEmail = '';
    public string $marketplaceName = 'Ecommet';
    public string $officePhone = '+880 1300 000 000';
    public string $officeAddress = 'Sector 11, Uttara, Dhaka, Bangladesh';
    public string $officeHours = 'Sunday – Thursday: 9am – 6pm';

    public bool $sent = false;

    public function mount(): void
    {
        $settings = app(AppSettingRepository::class)->getByKeys([
            'app_name',
            'marketplace_name',
            'support_email',
        ]);

        $this->marketplaceName = $settings->get('marketplace_name')?->value
            ?? $settings->get('app_name')?->value
            ?? config('app.name', 'Ecommet');
        $this->supportEmail = $settings->get('support_email')?->value ?? (string) config('mail.from.address', '');
    }

    public function send(): void
    {
        $validated = $this->validate([
            'contactName' => ['required', 'string', 'max:255'],
            'contactEmail' => ['required', 'email', 'max:255'],
            'contactSubject' => ['required', 'string', 'max:255'],
            'contactMessage' => ['required', 'string', 'min:10', 'max:5000'],
        ]);

        $recipient = $this->supportEmail ?: (string) config('mail.from.address', '');

        if (blank($recipient)) {
            $this->addError('contactMessage', 'Support email is not configured yet.');

            return;
        }

        $body = '<h2>New website contact message</h2>'
            . '<p><strong>Name:</strong> ' . e($validated['contactName']) . '</p>'
            . '<p><strong>Email:</strong> ' . e($validated['contactEmail']) . '</p>'
            . '<p><strong>Subject:</strong> ' . e($validated['contactSubject']) . '</p>'
            . '<p><strong>Message:</strong></p>'
            . '<p>' . nl2br(e($validated['contactMessage'])) . '</p>';

        try {
            $mail = new HtmlTemplateMail(
                'Website Contact: ' . $validated['contactSubject'],
                $body,
            );

            $mail->replyTo($validated['contactEmail'], $validated['contactName']);

            Mail::to($recipient)->send($mail);
        } catch (Throwable $exception) {
            report($exception);
            $this->addError('contactMessage', 'Unable to send your message right now. Please try again later.');

            return;
        }

        $this->reset(['contactName', 'contactEmail', 'contactSubject', 'contactMessage']);
        $this->resetErrorBag();
        $this->sent = true;
    }

    public function render()
    {
        return view('livewire.website.contact')
            ->layout('layouts.website', ['title' => 'Contact Us — Ecommet']);
    }
}
