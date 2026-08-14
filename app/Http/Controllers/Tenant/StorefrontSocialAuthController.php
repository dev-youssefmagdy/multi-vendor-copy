<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

class StorefrontSocialAuthController extends Controller
{
    public function redirectToGoogle(): RedirectResponse
    {
        return Socialite::driver('google')
            ->redirectUrl(route('tenant.storefront.social.google.callback'))
            ->redirect();
    }

    public function handleGoogleCallback(): RedirectResponse
    {
        $socialUser = Socialite::driver('google')
            ->redirectUrl(route('tenant.storefront.social.google.callback'))
            ->user();

        return $this->loginOrRegister('google', $socialUser);
    }

    public function redirectToApple(): RedirectResponse
    {
        return Socialite::driver('apple')
            ->redirectUrl(route('tenant.storefront.social.apple.callback'))
            ->stateless()
            ->redirect();
    }

    public function handleAppleCallback(): RedirectResponse
    {
        $socialUser = Socialite::driver('apple')
            ->redirectUrl(route('tenant.storefront.social.apple.callback'))
            ->stateless()
            ->user();

        return $this->loginOrRegister('apple', $socialUser);
    }

    private function loginOrRegister(string $provider, SocialiteUser $socialUser): RedirectResponse
    {
        $email = $socialUser->getEmail();

        if (!$email) {
            return redirect()
                ->route('tenant.storefront.login')
                ->with('social_login_error', __('We could not retrieve your email from :provider. Please sign in with email instead.', ['provider' => ucfirst($provider)]));
        }

        $customer = Customer::query()->where('email', $email)->first();

        if ($customer) {
            if (!$customer->provider) {
                $customer->forceFill([
                    'provider' => $provider,
                    'provider_id' => $socialUser->getId(),
                    'avatar' => $customer->avatar ?: $socialUser->getAvatar(),
                ])->save();
            }
        } else {
            $customer = Customer::create([
                // Apple only sends the name on the very first authorization.
                'full_name' => $socialUser->getName() ?: $socialUser->getNickname() ?: Str::before($email, '@'),
                'email' => $email,
                'password' => Hash::make(Str::random(40)),
                'active' => true,
                'language' => session('storefront_language'),
                'provider' => $provider,
                'provider_id' => $socialUser->getId(),
                'avatar' => $socialUser->getAvatar(),
            ]);
        }

        Auth::guard('storefront')->login($customer, remember: true);

        if ($customer->language) {
            session([
                'storefront_language' => $customer->language,
                'storefront_language_manual' => true,
            ]);
        }

        return redirect()->to(session()->pull('url.intended', route('tenant.storefront.profile')));
    }
}
