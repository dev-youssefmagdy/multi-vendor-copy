<?php

namespace App\Livewire\Admin\Setting;

use App\Livewire\Admin\Base\ContentPage;
use App\Livewire\Admin\Concerns\InteractsWithAdminUi;
use App\Repositories\AppSettingRepository;
use App\Services\AppSettingService;
use App\Services\Mail\TemplateMailService;

class EmailConfigurationPage extends ContentPage
{
    use InteractsWithAdminUi;

    public string $mailMailer = 'smtp';
    public string $mailHost = '';
    public string $mailPort = '587';
    public string $mailUsername = '';
    public string $mailPassword = '';
    public string $mailEncryption = 'tls';
    public string $mailFromAddress = '';
    public string $mailFromName = '';
    public bool $mailQueue = true;

    public bool $showTestEmailModal = false;
    public string $testEmailAddress = '';

    public function mount(): void
    {
        $settings = app(AppSettingRepository::class)->getByKeys([
            'mail_mailer',
            'mail_host',
            'mail_port',
            'mail_username',
            'mail_password',
            'mail_encryption',
            'mail_from_address',
            'mail_from_name',
            'mail_queue',
        ]);

        $this->mailMailer = $settings->get('mail_mailer')?->value ?? 'smtp';
        $this->mailHost = $settings->get('mail_host')?->value ?? '';
        $this->mailPort = $settings->get('mail_port')?->value ?? '587';
        $this->mailUsername = $settings->get('mail_username')?->value ?? '';
        $this->mailPassword = $settings->get('mail_password')?->value ?? '';
        $this->mailEncryption = $settings->get('mail_encryption')?->value ?? 'tls';
        $this->mailFromAddress = $settings->get('mail_from_address')?->value ?? '';
        $this->mailFromName = $settings->get('mail_from_name')?->value ?? config('app.name', 'Multi Vendor');
        $this->mailQueue = (bool) ($settings->get('mail_queue')?->value ?? true);
    }

    protected function pageMeta(): array
    {
        return [
            'title' => 'Email Configuration',
            'badge' => 'Communication',
            'description' => 'Manage central outbound mail transport, sender defaults, and queue behavior for system notifications.',
            'bullets' => [
                'Store central mail configuration in the app settings table.',
                'Keep sender details consistent across billing and operational emails.',
                'Use the same configuration surface for queued and direct delivery.',
            ],
        ];
    }

    public function save(AppSettingService $service): void
    {
        $validated = $this->validate([
            'mailMailer' => ['required', 'string', 'max:50'],
            'mailHost' => ['nullable', 'string', 'max:255'],
            'mailPort' => ['required', 'string', 'max:10'],
            'mailUsername' => ['nullable', 'string', 'max:255'],
            'mailPassword' => ['nullable', 'string', 'max:255'],
            'mailEncryption' => ['nullable', 'string', 'max:50'],
            'mailFromAddress' => ['nullable', 'email', 'max:255'],
            'mailFromName' => ['nullable', 'string', 'max:255'],
            'mailQueue' => ['boolean'],
        ]);

        $service->saveMany([
            'mail_mailer' => ['value' => $validated['mailMailer']],
            'mail_host' => ['value' => $validated['mailHost']],
            'mail_port' => ['value' => $validated['mailPort']],
            'mail_username' => ['value' => $validated['mailUsername']],
            'mail_password' => ['value' => $validated['mailPassword'], 'type' => 'secret'],
            'mail_encryption' => ['value' => $validated['mailEncryption']],
            'mail_from_address' => ['value' => $validated['mailFromAddress']],
            'mail_from_name' => ['value' => $validated['mailFromName']],
            'mail_queue' => ['value' => $validated['mailQueue'] ? '1' : '0', 'type' => 'boolean'],
        ]);

        $this->toast('Email configuration saved successfully.');
    }

    public function openTestEmailModal(): void
    {
        $this->resetErrorBag();
        $this->testEmailAddress = $this->mailFromAddress;
        $this->showTestEmailModal = true;
    }

    public function closeTestEmailModal(): void
    {
        $this->showTestEmailModal = false;
    }

    public function sendTestEmail(TemplateMailService $mailService): void
    {
        $validated = $this->validate([
            'testEmailAddress' => ['required', 'email', 'max:255'],
        ]);

        try {
            $result = $mailService->sendTest([
                'mailer' => $this->mailMailer,
                'host' => $this->mailHost,
                'port' => $this->mailPort,
                'username' => $this->mailUsername,
                'password' => $this->mailPassword,
                'encryption' => $this->mailEncryption,
                'from_address' => $this->mailFromAddress,
                'from_name' => $this->mailFromName,
            ], $validated['testEmailAddress']);
        } catch (\Throwable $e) {
            $this->toast('Failed to send test email: '.$e->getMessage(), 'error');

            return;
        }

        $this->toast($result['message'], $result['success'] ? 'success' : 'error');

        if ($result['success']) {
            $this->showTestEmailModal = false;
        }
    }

    protected function pageData(): array
    {
        $stats = app(AppSettingRepository::class)->stats();

        return array_merge(parent::pageData(), [
            'formAction' => 'save',
            'submitLabel' => 'Save Email Settings',
            'secondaryActions' => [
                ['label' => 'Send Test Email', 'method' => 'openTestEmailModal'],
            ],
            'modalView' => 'livewire.admin.setting.partials.test-email-modal',
            'cards' => [
                ['label' => 'Settings', 'value' => number_format($stats['total']), 'caption' => 'Central configuration records', 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
                ['label' => 'Public', 'value' => number_format($stats['public']), 'caption' => 'Public-facing configuration entries', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
            ],
            'fieldGroups' => [
                [
                    'title' => 'SMTP Transport',
                    'description' => 'Core transport settings for marketplace emails.',
                    'gridClass' => 'form-grid-2',
                    'fields' => [
                        ['label' => 'Mailer', 'model' => 'mailMailer'],
                        ['label' => 'Host', 'model' => 'mailHost'],
                        ['label' => 'Port', 'model' => 'mailPort'],
                        ['label' => 'Encryption', 'model' => 'mailEncryption', 'type' => 'select', 'options' => ['tls' => 'TLS', 'ssl' => 'SSL', '' => 'None']],
                        ['label' => 'Username', 'model' => 'mailUsername'],
                        ['label' => 'Password', 'model' => 'mailPassword', 'type' => 'password'],
                    ],
                ],
                [
                    'title' => 'Sender Defaults',
                    'description' => 'Outgoing identity for system notifications and invoices.',
                    'gridClass' => 'form-grid-2',
                    'fields' => [
                        ['label' => 'From Address', 'model' => 'mailFromAddress', 'type' => 'email'],
                        ['label' => 'From Name', 'model' => 'mailFromName'],
                        ['label' => 'Queue Email Sending', 'model' => 'mailQueue', 'type' => 'checkbox', 'toggleLabel' => 'Send emails through the queue'],
                    ],
                ],
            ],
        ]);
    }
}
