<?php

namespace App\Services\Mail;

use App\Models\AppSetting;
use App\Models\Tenant;
use App\Models\Tenant\Setting;

class MailConfigurationResolver
{
    /** Mailers a tenant may pick in the panel (value => label). */
    public const TENANT_MAILERS = [
        'smtp' => 'SMTP',
        'sendmail' => 'Sendmail',
        'log' => 'Log (no real email, for testing)',
    ];

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

    public function tenant(?Tenant $tenant = null): array
    {
        $central = $this->central();

        if (!tenant() && !$tenant) {
            return $central;
        }

        $overrides = Setting::query()
            ->whereIn('name', $this->mailKeys())
            ->pluck('value', 'name')
            ->all();

        return [
            'mailer' => self::normalizeMailer($this->tenantOverride($overrides, 'mail_mailer', $central['mailer'])),
            'host' => $this->tenantOverride($overrides, 'mail_host', $central['host']),
            'port' => $this->tenantOverride($overrides, 'mail_port', $central['port']),
            'username' => $this->tenantOverride($overrides, 'mail_username', $central['username']),
            'password' => $this->tenantOverride($overrides, 'mail_password', $central['password']),
            'encryption' => $this->tenantOverride($overrides, 'mail_encryption', $central['encryption']),
            'from_address' => $this->tenantOverride($overrides, 'mail_from_address', $central['from_address']),
            'from_name' => $this->tenantOverride($overrides, 'mail_from_name', $central['from_name']),
            'queue' => $central['queue'],
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
