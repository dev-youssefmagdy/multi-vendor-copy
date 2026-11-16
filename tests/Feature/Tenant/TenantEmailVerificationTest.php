<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Models\Tenant\Setting;
use App\Services\Mail\MailConfigurationResolver;
use App\Services\Mail\TenantMailHealth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;
use Tests\Feature\Tenant\Concerns\SetsUpTenantPanel;
use Tests\TestCase;

/**
 * "Resend verification email" in the Account Setup sidebar (client report 2026-11-16:
 * "I sent the verification email but nothing arrived"). The link is always sent through
 * the central (platform) mail configuration, never the store's own SMTP, and the AJAX
 * sidebar form gets a JSON message it can show.
 */
class TenantEmailVerificationTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpTenantPanel;

    /** @var list<\Illuminate\Mail\SentMessage> */
    private array $sent = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenantPanel();
        $this->tenant->run(fn () => $this->admin->forceFill(['tour_seen_at' => now(), 'email_verified_at' => null])->save());

        Event::listen(MessageSent::class, fn (MessageSent $event) => $this->sent[] = $event->sent);
    }

    protected function tearDown(): void
    {
        $this->tearDownTenantPanel();
        parent::tearDown();
    }

    public function test_resend_goes_through_central_mail_even_when_the_store_has_working_smtp(): void
    {
        // The store has its own working, verified mail server.
        $this->tenant->run(function () {
            Setting::create(['name' => 'mail_mailer', 'value' => 'array', 'type' => 'string', 'group' => 'mail']);
            Setting::create(['name' => 'mail_from_address', 'value' => 'shop@example.com', 'type' => 'string', 'group' => 'mail']);
            app(TenantMailHealth::class)->markVerified(app(MailConfigurationResolver::class)->tenantCustom());
        });

        $this->actingAsTenantAdmin()
            ->json('post', $this->tenantUrl('/admin/email/verification-notification'))
            ->assertOk()
            ->assertJsonPath('message', fn ($message) => str_contains($message, 'admin@panel-test.example.com'));

        $this->assertCount(1, $this->sent);
        $message = $this->sent[0]->getOriginalMessage();
        $central = $this->tenant->run(fn () => app(MailConfigurationResolver::class)->central());

        $this->assertSame('admin@panel-test.example.com', $message->getTo()[0]->getAddress());
        $this->assertSame($central['from_address'], $message->getFrom()[0]->getAddress());
        $this->assertNotSame('shop@example.com', $message->getFrom()[0]->getAddress());
        $this->assertStringContainsString('/email/verify/', $message->getHtmlBody());
    }

    public function test_already_verified_admin_gets_a_message_and_no_email(): void
    {
        $this->tenant->run(fn () => $this->admin->forceFill(['email_verified_at' => now()])->save());

        $this->actingAsTenantAdmin()
            ->json('post', $this->tenantUrl('/admin/email/verification-notification'))
            ->assertOk()
            ->assertJsonPath('message', 'Your email is already verified.');

        $this->assertSame([], $this->sent);
    }

    public function test_plain_form_post_redirects_back_with_a_status(): void
    {
        $this->actingAsTenantAdmin()
            ->from($this->tenantUrl('/admin/dashboard'))
            ->post($this->tenantUrl('/admin/email/verification-notification'))
            ->assertRedirect()
            ->assertSessionHas('status_type', 'success');

        $this->assertCount(1, $this->sent);
    }
}
