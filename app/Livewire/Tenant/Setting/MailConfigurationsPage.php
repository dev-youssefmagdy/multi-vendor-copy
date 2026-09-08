<?php

namespace App\Livewire\Tenant\Setting;

use App\Livewire\Tenant\Base\ContentPage;
use App\Livewire\Tenant\Concerns\InteractsWithTenantUi;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Services\Mail\MailConfigurationResolver;
use App\Services\Mail\TemplateMailService;
use App\Services\Tenant\TenantPanelService;

class MailConfigurationsPage extends ContentPage
{
    use InteractsWithTenantUi;

    public string $mailMailer = '';
    public string $mailHost = '';
    public string $mailPort = '';
    public string $mailUsername = '';
    public string $mailPassword = '';
    public string $mailEncryption = '';
    public string $mailFromAddress = '';
    public string $mailFromName = '';

    public bool $showTestEmailModal = false;
    public string $testEmailAddress = '';

    public function mount(): void
    {
        $settings = app(TenantPanelRepository::class)->emailSettings();
        $this->mailMailer = (string) ($settings['mail_mailer'] ?? '');
        $this->mailHost = (string) ($settings['mail_host'] ?? '');
        $this->mailPort = (string) ($settings['mail_port'] ?? '');
        $this->mailUsername = (string) ($settings['mail_username'] ?? '');
        $this->mailPassword = (string) ($settings['mail_password'] ?? '');
        $this->mailEncryption = (string) ($settings['mail_encryption'] ?? '');
        $this->mailFromAddress = (string) ($settings['mail_from_address'] ?? '');
        $this->mailFromName = (string) ($settings['mail_from_name'] ?? '');
    }

    protected function pageMeta(): array
    {
        return [
            'title' => 'Mail Configurations',
            'badge' => 'Settings',
            'description' => 'Store tenant-specific outbound mail overrides for storefront notifications.',
            'contentIntro' => 'Leave these fields blank to use the central mail configuration. Any non-empty value becomes a tenant override for outbound storefront emails.',
            'formAction' => 'save',
            'submitLabel' => 'Save Mail Settings',
            'secondaryActions' => [
                ['label' => 'Send Test Email', 'method' => 'openTestEmailModal'],
            ],
            'modalView' => 'livewire.tenant.setting.partials.test-email-modal',
            'fieldGroups' => [
                [
                    'title' => 'SMTP Settings',
                    'description' => 'Only fill the values you want this tenant to override. Blank values automatically fall back to the central configuration.',
                    'gridClass' => 'form-grid-2',
                    'fields' => [
                        ['label' => 'Mailer', 'model' => 'mailMailer'],
                        ['label' => 'Host', 'model' => 'mailHost'],
                        ['label' => 'Port', 'model' => 'mailPort'],
                        ['label' => 'Encryption', 'model' => 'mailEncryption'],
                        ['label' => 'Username', 'model' => 'mailUsername'],
                        ['label' => 'Password', 'model' => 'mailPassword', 'type' => 'password'],
                        ['label' => 'From Address', 'model' => 'mailFromAddress'],
                        ['label' => 'From Name', 'model' => 'mailFromName'],
                    ],
                ]
            ],
        ];
    }

    public function save(TenantPanelService $service): void
    {
        $validated = $this->validate([
            'mailMailer' => ['nullable', 'string', 'max:50'],
            'mailHost' => ['nullable', 'string', 'max:255'],
            'mailPort' => ['nullable', 'string', 'max:10'],
            'mailUsername' => ['nullable', 'string', 'max:255'],
            'mailPassword' => ['nullable', 'string', 'max:255'],
            'mailEncryption' => ['nullable', 'string', 'max:50'],
            'mailFromAddress' => ['nullable', 'email', 'max:255'],
            'mailFromName' => ['nullable', 'string', 'max:255'],
        ]);

        $service->saveMailSettings([
            'mail_mailer' => $validated['mailMailer'],
            'mail_host' => $validated['mailHost'],
            'mail_port' => $validated['mailPort'],
            'mail_username' => $validated['mailUsername'],
            'mail_password' => $validated['mailPassword'],
            'mail_encryption' => $validated['mailEncryption'],
            'mail_from_address' => $validated['mailFromAddress'],
            'mail_from_name' => $validated['mailFromName'],
        ]);

        $this->toast('Mail configuration overrides saved successfully. Blank fields will continue using the central mail configuration.');
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

    public function sendTestEmail(TemplateMailService $mailService, MailConfigurationResolver $resolver): void
    {
        $validated = $this->validate([
            'testEmailAddress' => ['required', 'email', 'max:255'],
        ]);

        $central = $resolver->central();

        $result = $mailService->sendTest([
            'mailer' => $this->mailMailer ?: $central['mailer'],
            'host' => $this->mailHost ?: $central['host'],
            'port' => $this->mailPort ?: $central['port'],
            'username' => $this->mailUsername ?: $central['username'],
            'password' => $this->mailPassword ?: $central['password'],
            'encryption' => $this->mailEncryption ?: $central['encryption'],
            'from_address' => $this->mailFromAddress ?: $central['from_address'],
            'from_name' => $this->mailFromName ?: $central['from_name'],
        ], $validated['testEmailAddress']);

        $this->toast($result['message'], $result['success'] ? 'success' : 'error');

        if ($result['success']) {
            $this->showTestEmailModal = false;
        }
    }
}
