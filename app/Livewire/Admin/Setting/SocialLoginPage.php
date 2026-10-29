<?php

namespace App\Livewire\Admin\Setting;

use App\Livewire\Admin\Base\ContentPage;
use App\Livewire\Admin\Concerns\InteractsWithAdminUi;
use App\Models\AppSetting;
use App\Services\Auth\SocialLoginConfig;

class SocialLoginPage extends ContentPage
{
    use InteractsWithAdminUi;

    public bool $googleEnabled = false;
    public string $googleClientId = '';
    public string $googleClientSecret = '';
    public bool $googleHasSecret = false;

    public bool $appleEnabled = false;
    public string $appleClientId = '';
    public string $appleClientSecret = '';
    public bool $appleHasSecret = false;

    public function mount(): void
    {
        $config = app(SocialLoginConfig::class);

        foreach (SocialLoginConfig::PROVIDERS as $provider) {
            $values = $config->get($provider);
            $this->{$provider.'Enabled'} = $values['enabled'];
            $this->{$provider.'ClientId'} = $values['client_id'];
            $this->{$provider.'HasSecret'} = $values['client_secret'] !== '';
        }
    }

    protected function pageView(): string
    {
        return 'livewire.admin.setting.social-login';
    }

    protected function pageMeta(): array
    {
        return [
            'title' => 'Social Login',
            'badge' => 'Authentication',
            'description' => 'Let customers and store owners sign in with Google or Apple. Credentials are stored encrypted in the central database.',
        ];
    }

    public function save(): void
    {
        $v = $this->validate([
            'googleEnabled' => ['boolean'],
            'googleClientId' => ['nullable', 'string', 'max:255', 'required_if:googleEnabled,true'],
            'googleClientSecret' => ['nullable', 'string', 'max:4000'],
            'appleEnabled' => ['boolean'],
            'appleClientId' => ['nullable', 'string', 'max:255', 'required_if:appleEnabled,true'],
            'appleClientSecret' => ['nullable', 'string', 'max:4000'],
        ]);

        $config = app(SocialLoginConfig::class);

        foreach (SocialLoginConfig::PROVIDERS as $provider) {
            $secret = trim((string) $v[$provider.'ClientSecret']);

            if ($v[$provider.'Enabled'] && $secret === '' && ! $this->{$provider.'HasSecret'}) {
                $this->addError($provider.'ClientSecret', 'A client secret is required to enable '.ucfirst($provider).' sign-in.');

                return;
            }

            $this->put(SocialLoginConfig::key($provider, 'enabled'), $v[$provider.'Enabled'] ? '1' : '0', 'boolean');
            $this->put(SocialLoginConfig::key($provider, 'client_id'), trim((string) $v[$provider.'ClientId']), 'string');

            // Blank secret field = keep the stored one.
            if ($secret !== '') {
                $this->put(SocialLoginConfig::key($provider, 'client_secret'), $config->encryptSecret($secret), 'secret');
                $this->{$provider.'HasSecret'} = true;
                $this->{$provider.'ClientSecret'} = '';
            }
        }

        $this->toast('Social login settings saved successfully.');
    }

    private function put(string $key, string $value, string $type): void
    {
        // Written directly (not via AppSettingService) so secrets are never pushed to tenant databases.
        AppSetting::query()->updateOrCreate(['key' => $key], ['value' => $value, 'type' => $type, 'is_public' => false]);
    }

    protected function pageData(): array
    {
        $state = fn (bool $enabled, string $id, bool $hasSecret) => ! $enabled
            ? ['Disabled', 'muted']
            : ($id !== '' && $hasSecret ? ['Active', 'green'] : ['Incomplete', 'amber']);

        $providers = [
            'google' => [
                'name' => 'Google',
                'state' => $state($this->googleEnabled, $this->googleClientId, $this->googleHasSecret),
                'hasSecret' => $this->googleHasSecret,
                'idLabel' => 'Client ID',
                'secretLabel' => 'Client Secret',
                'secretHelp' => 'Paste a new value to replace the stored secret.',
                'uris' => [
                    'Central website' => route('website.social.google.callback'),
                    'Each store domain' => 'https://{store-domain}'.route('tenant.storefront.social.google.callback', [], false),
                ],
                'uriLabel' => 'Authorized redirect URIs',
                'steps' => [
                    'Open Google Cloud Console, create or select a project.',
                    'Go to APIs & Services → OAuth consent screen. Set the app name and support email, then publish the app.',
                    'Go to Credentials → Create credentials → OAuth client ID, and choose "Web application".',
                    'Add every redirect URI listed above under "Authorized redirect URIs".',
                    'Copy the Client ID and Client secret into the fields on this page.',
                    'Turn on "Enable Google sign-in" and save.',
                ],
            ],
            'apple' => [
                'name' => 'Apple',
                'state' => $state($this->appleEnabled, $this->appleClientId, $this->appleHasSecret),
                'hasSecret' => $this->appleHasSecret,
                'idLabel' => 'Services ID (Client ID)',
                'secretLabel' => 'Client Secret (JWT)',
                'secretHelp' => 'Paste a new JWT to replace the stored one. Apple JWTs expire after at most 6 months.',
                'uris' => [
                    'Central website' => route('website.social.apple.callback'),
                    'Each store domain' => 'https://{store-domain}'.route('tenant.storefront.social.apple.callback', [], false),
                ],
                'uriLabel' => 'Return URLs',
                'steps' => [
                    'In the Apple Developer portal (Certificates, IDs & Profiles), create an App ID with "Sign in with Apple" enabled.',
                    'Create a Services ID. Its identifier is your Client ID. Enable Sign in with Apple, set your domain and add every return URL listed above.',
                    'Under Keys, create a key with "Sign in with Apple" and download the .p8 file (it can only be downloaded once). Note the Key ID and your Team ID.',
                    'Generate the client secret JWT (ES256): iss = Team ID, sub = Services ID, aud = https://appleid.apple.com, kid = Key ID, signed with the .p8 key, valid up to 6 months.',
                    'Paste the Services ID and the JWT into the fields on this page.',
                    'Turn on "Enable Apple sign-in" and save. Regenerate the JWT before it expires.',
                ],
            ],
        ];

        return array_merge(parent::pageData(), [
            'providers' => $providers,
            'submitLabel' => 'Save Social Login Settings',
        ]);
    }
}
