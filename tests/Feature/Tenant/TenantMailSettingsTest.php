<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Livewire\Tenant\Storefront\SupportRequestForm;
use App\Services\Mail\MailConfigurationResolver;
use App\Services\Mail\TemplateMailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery;
use Tests\Feature\Tenant\Concerns\SetsUpTenantPanel;
use Tests\TestCase;

/**
 * Client report: SMTP saved in the vendor panel but no email arrived. The free-text
 * "Mailer" field held an email address (an unknown transport, so every send failed),
 * and the storefront contact form ignored the store's mail settings entirely.
 */
class TenantMailSettingsTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpTenantPanel;

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

    public function test_an_invalid_saved_mailer_is_treated_as_smtp(): void
    {
        $this->assertSame('smtp', MailConfigurationResolver::normalizeMailer('issamakassimi@gmail.com'));
        $this->assertSame('smtp', MailConfigurationResolver::normalizeMailer(''));
        $this->assertSame('smtp', MailConfigurationResolver::normalizeMailer(' SMTP '));
        $this->assertSame('log', MailConfigurationResolver::normalizeMailer('log'));

        $mailer = $this->tenant->run(function () {
            $this->setSetting('mail_mailer', 'issamakassimi@gmail.com');
            $this->setSetting('mail_host', 'smtp.gmail.com');

            return app(MailConfigurationResolver::class)->tenant()['mailer'];
        });

        $this->assertSame('smtp', $mailer);
    }

    public function test_panel_only_accepts_known_mailers_and_shows_a_dropdown(): void
    {
        $this->actingAsTenantAdmin()
            ->json('put', $this->tenantUrl('/admin/settings/mail'), ['mail_mailer' => 'issamakassimi@gmail.com'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('mail_mailer');

        $this->actingAsTenantAdmin()
            ->json('put', $this->tenantUrl('/admin/settings/mail'), ['mail_mailer' => 'smtp'])
            ->assertSuccessful();

        // A value saved before the fix (mail group, as the panel stores it) renders as SMTP in the dropdown.
        $this->tenant->run(fn () => \App\Models\Tenant\Setting::query()
            ->where('name', 'mail_mailer')
            ->update(['value' => 'issamakassimi@gmail.com', 'group' => 'mail']));

        $html = $this->actingAsTenantAdmin()
            ->get($this->tenantUrl('/admin/settings/mail'))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression('/<select[^>]*name="mail_mailer"/s', $html);
        $this->assertMatchesRegularExpression('/<option[^>]*value="smtp"[^>]*selected/s', $html);
        $this->assertStringNotContainsString('value="issamakassimi@gmail.com"', $html);
        $this->assertStringContainsString('App Password', $html);
    }

    public function test_contact_form_sends_through_the_store_mail_settings(): void
    {
        $adminEmail = $this->tenant->run(fn () => $this->admin->email);

        $service = Mockery::mock(TemplateMailService::class);
        $service->shouldReceive('sendTenantMessage')
            ->once()
            ->withArgs(fn ($to, $subject, $body, $replyTo, $replyName) => $to === $adminEmail
                && str_contains($body, 'My order has not arrived yet')
                && $replyTo === 'buyer@example.com'
                && $replyName === 'Buyer');
        $this->app->instance(TemplateMailService::class, $service);

        $this->tenant->run(function () {
            Livewire::test(SupportRequestForm::class)
                ->set('type', 'order')
                ->set('name', 'Buyer')
                ->set('email', 'buyer@example.com')
                ->set('message', 'My order has not arrived yet, please help.')
                ->call('send')
                ->assertHasNoErrors()
                ->assertSet('sent', true);
        });
    }

    public function test_contact_form_reports_failure_instead_of_claiming_success(): void
    {
        $service = Mockery::mock(TemplateMailService::class);
        $service->shouldReceive('sendTenantMessage')->once()->andThrow(new \RuntimeException('535 Username and Password not accepted'));
        $this->app->instance(TemplateMailService::class, $service);

        $this->tenant->run(function () {
            Livewire::test(SupportRequestForm::class)
                ->set('name', 'Buyer')
                ->set('email', 'buyer@example.com')
                ->set('message', 'My order has not arrived yet, please help.')
                ->call('send')
                ->assertHasErrors('message')
                ->assertSet('sent', false);
        });
    }
}
