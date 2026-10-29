<?php

namespace App\Services\Auth;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Crypt;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use Throwable;

/**
 * Google / Apple sign-in credentials, stored in the central `app_settings`
 * table (Admin > Settings > Social Login). Secrets are encrypted at rest.
 * .env / config/services.php are intentionally not consulted.
 */
class SocialLoginConfig
{
    public const PROVIDERS = ['google', 'apple'];

    public static function key(string $provider, string $field): string
    {
        return "social_login_{$provider}_{$field}";
    }

    /** @return list<string> every app_settings key owned by this feature */
    public static function allKeys(): array
    {
        $keys = [];
        foreach (self::PROVIDERS as $provider) {
            foreach (['enabled', 'client_id', 'client_secret'] as $field) {
                $keys[] = self::key($provider, $field);
            }
        }

        return $keys;
    }

    public function encryptSecret(string $value): string
    {
        return $value === '' ? '' : Crypt::encryptString($value);
    }

    /** @return array{enabled: bool, client_id: string, client_secret: string} */
    public function get(string $provider): array
    {
        $rows = AppSetting::query()
            ->whereIn('key', array_map(fn ($f) => self::key($provider, $f), ['enabled', 'client_id', 'client_secret']))
            ->pluck('value', 'key');

        $secret = (string) $rows->get(self::key($provider, 'client_secret'), '');

        try {
            $secret = $secret === '' ? '' : Crypt::decryptString($secret);
        } catch (Throwable) {
            $secret = '';
        }

        return [
            'enabled' => (bool) $rows->get(self::key($provider, 'enabled'), false),
            'client_id' => (string) $rows->get(self::key($provider, 'client_id'), ''),
            'client_secret' => $secret,
        ];
    }

    public function isEnabled(string $provider): bool
    {
        $config = $this->get($provider);

        return $config['enabled'] && $config['client_id'] !== '' && $config['client_secret'] !== '';
    }

    /** Socialite driver built from the central DB credentials. */
    public function driver(string $provider, string $redirectUrl): AbstractProvider
    {
        abort_unless($this->isEnabled($provider), 404);

        $config = $this->get($provider);

        if ($provider === 'apple') {
            return Socialite::driver('apple')->setConfig(
                new \SocialiteProviders\Manager\Config($config['client_id'], $config['client_secret'], $redirectUrl)
            );
        }

        return Socialite::buildProvider(\Laravel\Socialite\Two\GoogleProvider::class, [
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'redirect' => $redirectUrl,
        ]);
    }
}
