<?php

namespace App\Services\Mail;

use App\Models\AppSetting;
use App\Models\Tenant\Setting;

class MailConfigurationResolver
{
    /** Friendly labels for well-known mailers; any other configured mailer shows its own name. */
    private const MAILER_LABELS = [
        'smtp' => 'SMTP',
        'sendmail' => 'Sendmail',
        'ses' => 'Amazon SES',
        'postmark' => 'Postmark',
        'resend' => 'Resend',
        'mailgun' => 'Mailgun',
        'log' => 'Log (no real email, for testing)',
        'array' => 'Array (no real email, for testing)',
        'failover' => 'Failover',
        'roundrobin' => 'Round robin',
    ];

    /**
     * Every mailer configured in config/mail.php that a tenant can pick in the panel
     * (value => label), SMTP first.
     */
    public static function availableMailers(): array
    {
        $names = array_keys(array_filter((array) config('mail.mailers', []), 'is_array'));
        usort($names, fn ($a, $b) => ($b === 'smtp') <=> ($a === 'smtp'));

        $options = [];
        foreach ($names as $name) {
            $options[$name] = self::MAILER_LABELS[$name] ?? str($name)->headline()->toString();
        }

        return $options;
    }

    /**
     * A saved mailer that is not a configured transport (e.g. an email address typed
     * into the old free-text "Mailer" field) would make every send fail with
     * "Unsupported mail transport". Treat it as SMTP, which the SMTP fields describe.
     */
    public static function normalizeMailer(mixed $mailer): string
    {
        $mailer = strtolower(trim((string) $mailer));

        if ($mailer === '' || !is_array(config('mail.mailers.'.$mailer))) {
            return 'smtp';
        }

        return $mailer;
    }

    public function central(): array
    {
        $settings = $this->centralSettings();

        return [
            'mailer' => self::normalizeMailer($this->tenantOverride($settings, 'mail_mailer', config('mail.default', 'smtp'))),
            'host' => $settings['mail_host'] ?? config('mail.mailers.smtp.host'),
            'port' => $settings['mail_port'] ?? config('mail.mailers.smtp.port'),
            'username' => $settings['mail_username'] ?? config('mail.mailers.smtp.username'),
            'password' => $settings['mail_password'] ?? config('mail.mailers.smtp.password'),
            'encryption' => $settings['mail_encryption'] ?? config('mail.mailers.smtp.encryption'),
            'from_address' => $settings['mail_from_address'] ?? config('mail.from.address'),
            'from_name' => $settings['mail_from_name'] ?? config('mail.from.name'),
            'queue' => filter_var($settings['mail_queue'] ?? false, FILTER_VALIDATE_BOOL),
        ];
    }

    /**
     * The config the current store's emails are sent with: its own complete, healthy
     * mail settings, otherwise the central configuration as a whole. Settings are never
     * mixed field by field (e.g. central SMTP sending "from" the vendor's Gmail address
     * is rejected by receiving servers).
     */
    public function tenant(): array
    {
        if (! tenant()) {
            return $this->central();
        }

        return app(TenantMailHealth::class)->usableCustomConfig() ?? $this->central();
    }

    /**
     * The current store's own mail config, or null when it has none / it is incomplete
     * (then the store uses the central config). Health is tracked by TenantMailHealth.
     */
    public function tenantCustom(): ?array
    {
        if (! tenant()) {
            return null;
        }

        $values = array_map(
            fn ($value) => trim((string) $value),
            Setting::query()->whereIn('name', $this->mailKeys())->pluck('value', 'name')->all(),
        );

        return self::completeCustomConfig([
            'mailer' => $values['mail_mailer'] ?? '',
            'host' => $values['mail_host'] ?? '',
            'port' => $values['mail_port'] ?? '',
            'username' => $values['mail_username'] ?? '',
            'password' => $values['mail_password'] ?? '',
            'encryption' => $values['mail_encryption'] ?? '',
            'from_address' => $values['mail_from_address'] ?? '',
            'from_name' => $values['mail_from_name'] ?? '',
        ]);
    }

    /** Fields a store must fill for its own mail config to be used, per mailer. */
    public static function requiredCustomFields(string $mailer): array
    {
        return self::normalizeMailer($mailer) === 'smtp'
            ? ['host', 'port', 'username', 'password', 'from_address']
            : ['from_address'];
    }

    /**
     * Normalise raw store values into a sendable config, or null when they do not
     * describe a complete custom config.
     */
    public static function completeCustomConfig(array $values): ?array
    {
        $mailer = self::normalizeMailer($values['mailer'] ?? '');

        foreach (self::requiredCustomFields($mailer) as $field) {
            if (trim((string) ($values[$field] ?? '')) === '') {
                return null;
            }
        }

        return [
            'mailer' => $mailer,
            'host' => $mailer === 'smtp' ? $values['host'] : null,
            'port' => $mailer === 'smtp' ? $values['port'] : null,
            'username' => $mailer === 'smtp' ? $values['username'] : null,
            'password' => $mailer === 'smtp' ? $values['password'] : null,
            'encryption' => $mailer === 'smtp' ? (strtolower((string) ($values['encryption'] ?? '')) ?: null) : null,
            'from_address' => $values['from_address'],
            'from_name' => trim((string) ($values['from_name'] ?? '')) ?: (tenant()?->name ?: config('app.name')),
            'queue' => false,
        ];
    }

    public function centralSettings(): array
    {
        return $this->onCentral(fn() => AppSetting::query()
            ->whereIn('key', $this->mailKeys())
            ->pluck('value', 'key')
            ->all());
    }

    public function mailKeys(): array
    {
        return [
            'mail_mailer',
            'mail_host',
            'mail_port',
            'mail_username',
            'mail_password',
            'mail_encryption',
            'mail_from_address',
            'mail_from_name',
            'mail_queue',
        ];
    }

    protected function tenantOverride(array $overrides, string $key, mixed $fallback): mixed
    {
        if (!array_key_exists($key, $overrides)) {
            return $fallback;
        }

        $value = $overrides[$key];

        if (is_string($value) && trim($value) === '') {
            return $fallback;
        }

        return $value;
    }

    protected function onCentral(callable $callback): mixed
    {
        if (tenant()) {
            return tenancy()->central($callback);
        }

        return $callback();
    }
}
