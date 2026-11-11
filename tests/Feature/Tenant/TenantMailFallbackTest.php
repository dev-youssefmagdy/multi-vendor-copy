<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Models\Tenant\Setting;
use App\Models\Tenant\TenantNotification;
use App\Services\Mail\MailConfigurationResolver;
use App\Services\Mail\TemplateMailService;
use App\Services\Mail\TenantMailHealth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Tenant\Concerns\SetsUpTenantPanel;
use Tests\TestCase;

/**
 * A store sends through its own mail server only when that config is complete and
 * working; otherwise (or when it breaks) everything goes through the central mail
 * config and the vendor is told. Central mail is the "array" mailer in tests; a vendor
 * SMTP server at 127.0.0.1:1 refuses connections instantly.
 */
class TenantMailFallbackTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpTenantPanel;

    private const BROKEN_SMTP = [
        'mail_mailer' => 'smtp',
        'mail_host' => '127.0.0.1',
        'mail_port' => '1',
        'mail_username' => 'shop@gmail.com',
        'mail_password' => 'wrong-app-password',
        'mail_encryption' => '',
        'mail_from_address' => 'shop@gmail.com',
        'mail_from_name' => 'My Shop',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenantPanel();
        $this->tenant->run(fn () => $this->admin->forceFill(['tour_seen_at' => now()])->save());
    }

    protected function tearDown(): void
    {
        $this->tearDownTenantPanel();
        parent::tearDown();
    }

    public function test_store_without_mail_settings_uses_central_mail(): void
    {
        $this->actingAsTenantAdmin()
            ->json('put', $this->tenantUrl('/admin/settings/mail'), [])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->tenant->run(function () {
            $this->assertSame(TenantMailHealth::CENTRAL, app(TenantMailHealth::class)->status()['state']);
            $this->assertSame(app(MailConfigurationResolver::class)->central(), app(MailConfigurationResolver::class)->tenant());
        });
    }

    public function test_incomplete_smtp_settings_are_rejected_and_never_mixed_with_central(): void
    {
        $this->actingAsTenantAdmin()
            ->json('put', $this->tenantUrl('/admin/settings/mail'), ['mail_from_address' => 'shop@gmail.com'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['mail_host', 'mail_port', 'mail_username', 'mail_password']);

        // Even if a half config reaches the database, central is used as a whole: never
        // central SMTP sending "from" the vendor's Gmail address.
        $this->tenant->run(function () {
            Setting::create(['name' => 'mail_from_address', 'value' => 'shop@gmail.com', 'type' => 'string', 'group' => 'mail']);

            $resolver = app(MailConfigurationResolver::class);
            $this->assertNull($resolver->tenantCustom());
            $this->assertSame($resolver->central(), $resolver->tenant());
        });
    }

    public function test_working_settings_are_verified_on_save_and_used(): void
    {
        $this->actingAsTenantAdmin()
            ->json('put', $this->tenantUrl('/admin/settings/mail'), [
                'mail_mailer' => 'array',
                'mail_from_address' => 'shop@example.com',
                'mail_from_name' => 'My Shop',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.mail_status_html', fn ($html) => str_contains($html, 'shop@example.com'));

        $this->tenant->run(function () {
            $this->assertSame(TenantMailHealth::VERIFIED, app(TenantMailHealth::class)->status()['state']);
            $config = app(MailConfigurationResolver::class)->tenant();
            $this->assertSame('array', $config['mailer']);
            $this->assertSame('shop@example.com', $config['from_address']);
        });
    }

    public function test_broken_settings_are_saved_flagged_and_vendor_notified_while_central_keeps_sending(): void
    {
        $this->actingAsTenantAdmin()
            ->json('put', $this->tenantUrl('/admin/settings/mail'), self::BROKEN_SMTP)
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', fn ($message) => str_contains($message, 'platform mail server'))
            ->assertJsonPath('data.mail_status_html', fn ($html) => str_contains($html, 'not working'));

        $this->tenant->run(function () {
            // Values are kept so the vendor can correct them.
            $this->assertSame('127.0.0.1', Setting::query()->where('name', 'mail_host')->value('value'));

            $status = app(TenantMailHealth::class)->status();
            $this->assertSame(TenantMailHealth::FAILED, $status['state']);
            $this->assertNotEmpty($status['error']);

            $this->assertSame(1, TenantNotification::query()->where('type', 'mail_config_failed')->count());

            // Store emails go out through central without touching the broken server.
            $this->assertSame(app(MailConfigurationResolver::class)->central(), app(MailConfigurationResolver::class)->tenant());
            app(TemplateMailService::class)->sendTenantMessage('buyer@example.com', 'Hello', '<p>Hi</p>');
        });
    }

    public function test_server_that_breaks_after_verification_falls_back_to_central_and_notifies_once(): void
    {
        $this->tenant->run(function () {
            foreach (self::BROKEN_SMTP as $name => $value) {
                if ($value !== '') {
                    Setting::create(['name' => $name, 'value' => $value, 'type' => 'string', 'group' => 'mail']);
                }
            }

            $health = app(TenantMailHealth::class);
            $custom = app(MailConfigurationResolver::class)->tenantCustom();
            $health->markVerified($custom); // worked yesterday, password revoked today

            $mail = app(TemplateMailService::class);
            $mail->sendTenantMessage('buyer@example.com', 'Order 1', '<p>1</p>'); // no exception: central delivered
            $this->assertSame(TenantMailHealth::FAILED, $health->status()['state']);

            $mail->sendTenantMessage('buyer@example.com', 'Order 2', '<p>2</p>');
            $this->assertSame(1, TenantNotification::query()->where('type', 'mail_config_failed')->count());
        });
    }

    public function test_settings_saved_before_verification_existed_are_tried_and_self_verify(): void
    {
        $this->tenant->run(function () {
            Setting::create(['name' => 'mail_mailer', 'value' => 'array', 'type' => 'string', 'group' => 'mail']);
            Setting::create(['name' => 'mail_from_address', 'value' => 'legacy@example.com', 'type' => 'string', 'group' => 'mail']);

            $health = app(TenantMailHealth::class);
            $this->assertSame(TenantMailHealth::UNVERIFIED, $health->status()['state']);
            $this->assertSame('legacy@example.com', app(MailConfigurationResolver::class)->tenant()['from_address']);

            app(TemplateMailService::class)->sendTenantMessage('buyer@example.com', 'Hello', '<p>Hi</p>');
            $this->assertSame(TenantMailHealth::VERIFIED, $health->status()['state']);

            // Editing the settings invalidates the old verification.
            Setting::query()->where('name', 'mail_from_address')->update(['value' => 'other@example.com']);
            $this->assertSame(TenantMailHealth::UNVERIFIED, $health->status()['state']);
        });
    }

    public function test_successful_test_email_reactivates_a_failed_server(): void
    {
        $this->tenant->run(function () {
            Setting::create(['name' => 'mail_mailer', 'value' => 'array', 'type' => 'string', 'group' => 'mail']);
            Setting::create(['name' => 'mail_from_address', 'value' => 'shop@example.com', 'type' => 'string', 'group' => 'mail']);
            app(TenantMailHealth::class)->markFailed(app(MailConfigurationResolver::class)->tenantCustom(), 'Mailbox was full');
        });

        $this->actingAsTenantAdmin()
            ->json('post', $this->tenantUrl('/admin/settings/mail/test'), ['email' => 'me@example.com'])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->tenant->run(fn () => $this->assertSame(TenantMailHealth::VERIFIED, app(TenantMailHealth::class)->status()['state']));
    }

    public function test_settings_page_shows_the_mail_status(): void
    {
        $this->actingAsTenantAdmin()
            ->get($this->tenantUrl('/admin/settings/mail'))
            ->assertOk()
            ->assertSee('Using the platform mail server')
            ->assertSee('name="mail_encryption"', false);
    }
}
