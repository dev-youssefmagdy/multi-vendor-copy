<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Settings;

use App\Http\Requests\Tenant\Panel\TenantFormRequest;
use App\Services\Mail\MailConfigurationResolver;
use Illuminate\Validation\Rule;

/**
 * A store either leaves every connection field blank (platform mail server) or fills
 * a complete config for its own server; a half-filled config is rejected here instead
 * of silently falling back at send time.
 */
final class SaveMailSettingsRequest extends TenantFormRequest
{
    /** Filling any of these means the vendor wants to use their own mail server. */
    private const CUSTOM_TRIGGERS = ['mail_mailer', 'mail_host', 'mail_port', 'mail_username', 'mail_password', 'mail_from_address'];

    public const ENCRYPTIONS = ['ssl', 'tls'];

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('mail_encryption'))) {
            $this->merge(['mail_encryption' => strtolower(trim($this->input('mail_encryption')))]);
        }
    }

    public function rules(): array
    {
        $required = $this->wantsCustomMail()
            ? MailConfigurationResolver::requiredCustomFields((string) $this->input('mail_mailer'))
            : [];
        $presence = fn (string $field) => in_array($field, $required, true) ? 'required' : 'nullable';

        return [
            'mail_mailer' => ['nullable', 'string', Rule::in(array_keys(MailConfigurationResolver::availableMailers()))],
            'mail_host' => [$presence('host'), 'string', 'max:255'],
            'mail_port' => [$presence('port'), 'integer', 'between:1,65535'],
            'mail_username' => [$presence('username'), 'string', 'max:255'],
            'mail_password' => [$presence('password'), 'string', 'max:255'],
            'mail_encryption' => ['nullable', 'string', Rule::in(self::ENCRYPTIONS)],
            'mail_from_address' => [$presence('from_address'), 'email', 'max:255'],
            'mail_from_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function wantsCustomMail(): bool
    {
        foreach (self::CUSTOM_TRIGGERS as $field) {
            if (trim((string) $this->input($field, '')) !== '') {
                return true;
            }
        }

        return false;
    }

    public function messages(): array
    {
        return [
            'required' => __('The :attribute is required to send through your own mail server. Leave all fields blank to use the platform mail server.'),
        ];
    }

    public function attributes(): array
    {
        return [
            'mail_mailer' => 'mailer',
            'mail_host' => 'host',
            'mail_port' => 'port',
            'mail_username' => 'username',
            'mail_password' => 'password',
            'mail_encryption' => 'encryption',
            'mail_from_address' => 'from address',
            'mail_from_name' => 'from name',
        ];
    }
}
