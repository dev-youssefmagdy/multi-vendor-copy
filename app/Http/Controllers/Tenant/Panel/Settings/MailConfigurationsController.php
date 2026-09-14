<?php

declare(strict_types=1);

// ROUTES:
// Route::put('/mail', [MailConfigurationsController::class, 'update'])->name('mail.update');
// Route::post('/mail/validate', [MailConfigurationsController::class, 'validateUpdate'])->name('mail.validate');
// Route::post('/mail/test', [MailConfigurationsController::class, 'sendTest'])->name('mail.test');
// Route::post('/mail/test/validate', [MailConfigurationsController::class, 'validateSendTest'])->name('mail.test.validate');
// (all under the existing tenant.permission:settings.mail.manage middleware, next to the kept `mail` GET route)

namespace App\Http\Controllers\Tenant\Panel\Settings;

use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Http\Requests\Tenant\Panel\Settings\SaveMailSettingsRequest;
use App\Http\Requests\Tenant\Panel\Settings\SendTestEmailRequest;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Services\Mail\MailConfigurationResolver;
use App\Services\Mail\TemplateMailService;
use App\Services\Tenant\TenantPanelService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

final class MailConfigurationsController extends PanelController
{
    public function __construct(private readonly TenantPanelRepository $repo)
    {
    }

    public function index(): View
    {
        $settings = $this->repo->emailSettings();

        $values = [
            'mail_mailer' => (string) ($settings['mail_mailer'] ?? ''),
            'mail_host' => (string) ($settings['mail_host'] ?? ''),
            'mail_port' => (string) ($settings['mail_port'] ?? ''),
            'mail_username' => (string) ($settings['mail_username'] ?? ''),
            'mail_password' => (string) ($settings['mail_password'] ?? ''),
            'mail_encryption' => (string) ($settings['mail_encryption'] ?? ''),
            'mail_from_address' => (string) ($settings['mail_from_address'] ?? ''),
            'mail_from_name' => (string) ($settings['mail_from_name'] ?? ''),
        ];

        $groups = [
            [
                'title' => 'SMTP Settings',
                'description' => 'Only fill the values you want this tenant to override. Blank values automatically fall back to the central configuration.',
                'gridClass' => 'form-grid-2',
                'fields' => [
                    ['label' => 'Mailer', 'model' => 'mail_mailer'],
                    ['label' => 'Host', 'model' => 'mail_host'],
                    ['label' => 'Port', 'model' => 'mail_port'],
                    ['label' => 'Encryption', 'model' => 'mail_encryption'],
                    ['label' => 'Username', 'model' => 'mail_username'],
                    ['label' => 'Password', 'model' => 'mail_password', 'type' => 'password'],
                    ['label' => 'From Address', 'model' => 'mail_from_address'],
                    ['label' => 'From Name', 'model' => 'mail_from_name'],
                ],
            ],
        ];

        return view('tenant.pages.settings.mail.index', [
            'groups' => $groups,
            'values' => $values,
        ]);
    }

    public function update(SaveMailSettingsRequest $request, TenantPanelService $service): JsonResponse
    {
        $validated = $request->validated();

        $service->saveMailSettings([
            'mail_mailer' => $validated['mail_mailer'] ?? '',
            'mail_host' => $validated['mail_host'] ?? '',
            'mail_port' => $validated['mail_port'] ?? '',
            'mail_username' => $validated['mail_username'] ?? '',
            'mail_password' => $validated['mail_password'] ?? '',
            'mail_encryption' => $validated['mail_encryption'] ?? '',
            'mail_from_address' => $validated['mail_from_address'] ?? '',
            'mail_from_name' => $validated['mail_from_name'] ?? '',
        ]);

        return $this->success('Mail configuration overrides saved successfully. Blank fields will continue using the central mail configuration.');
    }

    public function validateUpdate(SaveMailSettingsRequest $request): JsonResponse
    {
        return $this->validFormResponse();
    }

    public function sendTest(SendTestEmailRequest $request, TemplateMailService $mailService, MailConfigurationResolver $resolver): JsonResponse
    {
        $settings = $this->repo->emailSettings();
        $central = $resolver->central();

        $result = $mailService->sendTest([
            'mailer' => (string) ($settings['mail_mailer'] ?? '') ?: $central['mailer'],
            'host' => (string) ($settings['mail_host'] ?? '') ?: $central['host'],
            'port' => (string) ($settings['mail_port'] ?? '') ?: $central['port'],
            'username' => (string) ($settings['mail_username'] ?? '') ?: $central['username'],
            'password' => (string) ($settings['mail_password'] ?? '') ?: $central['password'],
            'encryption' => (string) ($settings['mail_encryption'] ?? '') ?: $central['encryption'],
            'from_address' => (string) ($settings['mail_from_address'] ?? '') ?: $central['from_address'],
            'from_name' => (string) ($settings['mail_from_name'] ?? '') ?: $central['from_name'],
        ], $request->validated()['email']);

        if (! $result['success']) {
            return $this->failure($result['message'], 422);
        }

        return $this->success($result['message']);
    }

    public function validateSendTest(SendTestEmailRequest $request): JsonResponse
    {
        return $this->validFormResponse();
    }
}
