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
use App\Services\Mail\TenantMailHealth;
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

        $savedMailer = trim((string) ($settings['mail_mailer'] ?? ''));

        $values = [
            // An invalid saved value (e.g. an email typed in the old free-text field) shows as SMTP.
            'mail_mailer' => $savedMailer === '' ? '' : MailConfigurationResolver::normalizeMailer($savedMailer),
            'mail_host' => (string) ($settings['mail_host'] ?? ''),
            'mail_port' => (string) ($settings['mail_port'] ?? ''),
            'mail_username' => (string) ($settings['mail_username'] ?? ''),
            'mail_password' => (string) ($settings['mail_password'] ?? ''),
            'mail_encryption' => strtolower((string) ($settings['mail_encryption'] ?? '')),
            'mail_from_address' => (string) ($settings['mail_from_address'] ?? ''),
            'mail_from_name' => (string) ($settings['mail_from_name'] ?? ''),
        ];

        $groups = [
            [
                'title' => 'SMTP Settings',
                'description' => __('Fill in your own mail server to send store emails from your address, or leave every field blank to use the platform mail server. When you save, we send a confirmation email through your server; if it fails, your emails keep going out through the platform mail server and you are notified.'),
                'gridClass' => 'form-grid-2',
                'fields' => [
                    [
                        'label' => 'Mailer',
                        'model' => 'mail_mailer',
                        'type' => 'select',
                        'options' => ['' => __('Use central default')] + array_map(fn ($label) => __($label), MailConfigurationResolver::availableMailers()),
                        'help' => __('Choose SMTP to send through your own mail account (e.g. Gmail).'),
                    ],
                    ['label' => 'Host', 'model' => 'mail_host', 'placeholder' => 'smtp.gmail.com'],
                    ['label' => 'Port', 'model' => 'mail_port', 'placeholder' => '465 / 587'],
                    [
                        'label' => 'Encryption',
                        'model' => 'mail_encryption',
                        'type' => 'select',
                        'options' => ['' => __('None'), 'ssl' => 'SSL (port 465)', 'tls' => 'TLS / STARTTLS (port 587)'],
                    ],
                    ['label' => 'Username', 'model' => 'mail_username'],
                    [
                        'label' => 'Password',
                        'model' => 'mail_password',
                        'type' => 'password',
                        'help' => __('For Gmail, use a 16-character App Password (Google Account → Security → 2-Step Verification → App passwords), not your normal password.'),
                    ],
                    ['label' => 'From Address', 'model' => 'mail_from_address'],
                    ['label' => 'From Name', 'model' => 'mail_from_name'],
                ],
            ],
        ];

        return view('tenant.pages.settings.mail.index', [
            'groups' => $groups,
            'values' => $values,
            'mailStatus' => $this->health()->status(),
        ]);
    }

    /**
     * Save the vendor's settings, then prove them by sending a confirmation email through
     * their server. On failure the settings stay saved (so they can be corrected), the
     * store is marked "failed", the vendor is notified and emails keep using central mail.
     */
    public function update(SaveMailSettingsRequest $request, TenantPanelService $service, TemplateMailService $mailService): JsonResponse
    {
        $validated = $request->validated();

        $service->saveMailSettings([
            'mail_mailer' => $validated['mail_mailer'] ?? '',
            'mail_host' => $validated['mail_host'] ?? '',
            'mail_port' => (string) ($validated['mail_port'] ?? ''),
            'mail_username' => $validated['mail_username'] ?? '',
            'mail_password' => $validated['mail_password'] ?? '',
            'mail_encryption' => $validated['mail_encryption'] ?? '',
            'mail_from_address' => $validated['mail_from_address'] ?? '',
            'mail_from_name' => $validated['mail_from_name'] ?? '',
        ]);

        $health = $this->health();
        $custom = app(MailConfigurationResolver::class)->tenantCustom();

        if ($custom === null) {
            $health->clear();

            return $this->success(
                __('Mail settings saved. Your store emails are sent through the platform mail server.'),
                $this->statusPayload(),
            );
        }

        $result = $mailService->verifyConfig($custom);

        if ($result['success']) {
            $health->markVerified($custom);

            return $this->success(
                __('Mail settings saved and verified. Your store emails are now sent from :email.', ['email' => $custom['from_address']]),
                $this->statusPayload(),
            );
        }

        $health->markFailed($custom, (string) $result['error']);

        return response()->json([
            'success' => false,
            'message' => __('Settings saved, but we could not send through your mail server: :error. Your store emails will keep going out through the platform mail server until this is fixed.', ['error' => $health->status()['error']]),
            'errors' => [],
            'data' => $this->statusPayload(),
        ], 422);
    }

    public function validateUpdate(SaveMailSettingsRequest $request): JsonResponse
    {
        return $this->validFormResponse();
    }

    /**
     * Send a test email with the saved settings: through the store's own server when it
     * has a complete config (a success or failure updates its health, so a vendor can
     * recover after fixing their mailbox without editing anything), otherwise through
     * the platform mail server.
     */
    public function sendTest(SendTestEmailRequest $request, TemplateMailService $mailService, MailConfigurationResolver $resolver): JsonResponse
    {
        $recipient = $request->validated()['email'];
        $custom = $resolver->tenantCustom();

        if ($custom === null) {
            $sent = $mailService->sendCentralMessage(
                $recipient,
                __('Test email from :store', ['store' => tenant()?->name ?: config('app.name')]),
                '<p>'.e(__('Your store has no own mail server configured, so this test was sent through the platform mail server.')).'</p>',
            );

            return $sent
                ? $this->success(__('Test email sent to :email through the platform mail server (your store has no own mail server configured).', ['email' => $recipient]))
                : $this->failure(__('The platform mail server could not send the test email. Please contact support.'));
        }

        $result = $mailService->verifyConfig($custom, $recipient);
        $health = $this->health();

        if ($result['success']) {
            $health->markVerified($custom);

            return $this->success(__('Test email sent to :email through your mail server.', ['email' => $recipient]), $this->statusPayload());
        }

        $health->markFailed($custom, (string) $result['error']);

        return response()->json([
            'success' => false,
            'message' => __('Your mail server could not send the test email: :error. Store emails are being sent through the platform mail server.', ['error' => $health->status()['error']]),
            'errors' => [],
            'data' => $this->statusPayload(),
        ], 422);
    }

    public function validateSendTest(SendTestEmailRequest $request): JsonResponse
    {
        return $this->validFormResponse();
    }

    private function health(): TenantMailHealth
    {
        return app(TenantMailHealth::class);
    }

    private function statusPayload(): array
    {
        return [
            'mail_status_html' => view('tenant.pages.settings.mail.partials.status', ['mailStatus' => $this->health()->status()])->render(),
        ];
    }
}
