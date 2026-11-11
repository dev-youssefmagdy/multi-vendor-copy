<?php

declare(strict_types=1);

namespace App\Services\Mail;

use App\Enums\Tenant\SettingType;
use App\Models\Tenant\Setting;
use App\Services\TenantNotificationService;
use App\Support\CacheVersion;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Tracks whether the current store's own mail server works.
 *
 * A store's mail config is all-or-nothing: it is used only when it is complete
 * (see MailConfigurationResolver::tenantCustom()) and not known to be broken.
 * States:
 *  - central     no complete custom config; everything goes through the platform mailer
 *  - unverified  complete config never checked (saved before verification existed,
 *                or edited outside the panel); tried first, with central fallback
 *  - verified    a verification or real send succeeded with exactly these settings
 *  - failed      the last verification or send failed; central only until the vendor
 *                re-saves or sends a successful test email
 *
 * The state is stored with a fingerprint of the settings it applies to, so any
 * change to the settings drops it back to "unverified".
 */
class TenantMailHealth
{
    /** Stored as separate rows: settings.value is varchar(255). */
    public const SETTINGS = [
        'state' => 'mail_status_state',
        'error' => 'mail_status_error',
        'checked_at' => 'mail_status_checked_at',
        'fingerprint' => 'mail_status_fingerprint',
    ];
    public const GROUP = 'mail_health';

    public const CENTRAL = 'central';
    public const UNVERIFIED = 'unverified';
    public const VERIFIED = 'verified';
    public const FAILED = 'failed';

    public function __construct(
        private readonly MailConfigurationResolver $resolver,
        private readonly TenantNotificationService $notifications,
    ) {}

    /**
     * @return array{state: string, error: ?string, checked_at: ?CarbonImmutable, config: ?array}
     */
    public function status(): array
    {
        $custom = $this->resolver->tenantCustom();

        if ($custom === null) {
            return ['state' => self::CENTRAL, 'error' => null, 'checked_at' => null, 'config' => null];
        }

        $stored = $this->stored();

        if (($stored['fingerprint'] ?? null) !== $this->fingerprint($custom)
            || ! in_array($stored['state'] ?? null, [self::VERIFIED, self::FAILED], true)) {
            return ['state' => self::UNVERIFIED, 'error' => null, 'checked_at' => null, 'config' => $custom];
        }

        return [
            'state' => $stored['state'],
            'error' => $stored['error'] ?? null,
            'checked_at' => isset($stored['checked_at']) ? CarbonImmutable::parse($stored['checked_at']) : null,
            'config' => $custom,
        ];
    }

    /** The store's own config when it should be tried for a real send, otherwise null (use central). */
    public function usableCustomConfig(): ?array
    {
        $status = $this->status();

        return $status['state'] === self::FAILED ? null : $status['config'];
    }

    public function markVerified(array $config): void
    {
        $this->store([
            'state' => self::VERIFIED,
            'error' => null,
            'checked_at' => now()->toIso8601String(),
            'fingerprint' => $this->fingerprint($config),
        ]);
    }

    /**
     * Record a failure for these settings and tell the vendor. With $onlyOnChange
     * (real sends) the vendor is notified once per breakage, not once per email.
     */
    public function markFailed(array $config, string $error, bool $onlyOnChange = false): void
    {
        $previous = $this->status();
        $error = $this->cleanError($error);

        $this->store([
            'state' => self::FAILED,
            'error' => $error,
            'checked_at' => now()->toIso8601String(),
            'fingerprint' => $this->fingerprint($config),
        ]);

        if ($onlyOnChange && $previous['state'] === self::FAILED) {
            return;
        }

        $this->notifyVendor($error);
    }

    /** Called after a real send through the store's server succeeded. */
    public function recordSuccess(array $config): void
    {
        if ($this->status()['state'] !== self::VERIFIED) {
            $this->markVerified($config);
        }
    }

    public function clear(): void
    {
        Setting::query()->whereIn('name', self::SETTINGS)->delete();
        CacheVersion::bump('Setting');
    }

    public function fingerprint(array $config): string
    {
        $keys = ['mailer', 'host', 'port', 'username', 'password', 'encryption', 'from_address'];

        return hash('sha256', json_encode(array_map(
            fn (string $key) => trim((string) ($config[$key] ?? '')),
            array_combine($keys, $keys),
        )));
    }

    protected function notifyVendor(string $error): void
    {
        $tenant = tenant();

        if (! $tenant) {
            return;
        }

        $title = __('Your store mail server is not working');
        $message = __('We could not send email through your mail settings (:error). Your store emails are being sent through the platform mail server until you fix them in Settings → Mail Configurations.', ['error' => $error]);

        try {
            $this->notifications->notify($tenant, 'mail_config_failed', $title, $message, [
                'url' => route('tenant.settings.mail', absolute: false),
            ]);
        } catch (Throwable $e) {
            Log::warning('Could not create mail failure notification', ['tenant_id' => $tenant->getTenantKey(), 'error' => $e->getMessage()]);
        }

        // Their own server is broken, so this one goes through the platform mailer.
        try {
            app(TemplateMailService::class)->sendCentralMessage(
                (string) $tenant->email,
                $title,
                '<p>'.e($message).'</p>',
            );
        } catch (Throwable $e) {
            Log::warning('Could not email store owner about mail failure', ['tenant_id' => $tenant->getTenantKey(), 'error' => $e->getMessage()]);
        }
    }

    protected function cleanError(string $error): string
    {
        $error = trim(preg_replace('/\s+/', ' ', $error) ?? '');

        return Str::limit($error !== '' ? $error : __('Unknown mail error'), 240);
    }

    protected function stored(): array
    {
        $values = Setting::query()->whereIn('name', self::SETTINGS)->pluck('value', 'name')->all();

        $stored = [];
        foreach (self::SETTINGS as $key => $name) {
            $stored[$key] = $values[$name] ?? null;
        }

        return $stored;
    }

    protected function store(array $payload): void
    {
        foreach (self::SETTINGS as $key => $name) {
            if (($payload[$key] ?? null) === null) {
                Setting::query()->where('name', $name)->delete();
                continue;
            }

            Setting::query()->updateOrCreate(
                ['name' => $name],
                ['value' => (string) $payload[$key], 'type' => SettingType::String->value, 'group' => self::GROUP],
            );
        }

        CacheVersion::bump('Setting');
    }
}
