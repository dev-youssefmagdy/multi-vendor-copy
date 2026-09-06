<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\PendingRegistration;
use App\Models\TenantOwner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

class CentralSocialAuthController extends Controller
{
    public function redirectToGoogle(Request $request, string $intent): RedirectResponse
    {
        $this->stashIntent($request, $intent);

        return Socialite::driver('google')
            ->redirectUrl(route('website.social.google.callback'))
            ->redirect();
    }

    public function handleGoogleCallback(): \Illuminate\Http\Response|RedirectResponse
    {
        $socialUser = Socialite::driver('google')
            ->redirectUrl(route('website.social.google.callback'))
            ->user();

        return $this->handle($socialUser, 'google');
    }

    public function redirectToApple(Request $request, string $intent): RedirectResponse
    {
        $this->stashIntent($request, $intent);

        return Socialite::driver('apple')
            ->redirectUrl(route('website.social.apple.callback'))
            ->stateless()
            ->redirect();
    }

    public function handleAppleCallback(): \Illuminate\Http\Response|RedirectResponse
    {
        $socialUser = Socialite::driver('apple')
            ->redirectUrl(route('website.social.apple.callback'))
            ->stateless()
            ->user();

        return $this->handle($socialUser, 'apple');
    }

    private function stashIntent(Request $request, string $intent): void
    {
        $validIntents = ['login', 'register', 'register-popup'];

        session([
            'central_social_intent' => in_array($intent, $validIntents, true) ? $intent : 'login',
            'central_social_package_id' => $request->query('package_id'),
        ]);
    }

    private function handle(SocialiteUser $socialUser, string $provider): \Illuminate\Http\Response|RedirectResponse
    {
        $intent = session()->pull('central_social_intent', 'login');
        $packageId = session()->pull('central_social_package_id');

        $email = $socialUser->getEmail();

        if ($intent === 'register-popup') {
            if (!$email) {
                return $this->popupError(
                    __('We could not retrieve your email from :provider. Please try again.', ['provider' => ucfirst($provider)])
                );
            }

            $email = strtolower(trim($email));

            return $this->popupSuccess(
                email: $email,
                name: $socialUser->getName() ?: $socialUser->getNickname() ?: Str::before($email, '@'),
                provider: $provider,
                existingAccount: TenantOwner::query()->where('email', $email)->exists(),
                providerId: $socialUser->getId(),
                avatar: $socialUser->getAvatar(),
            );
        }

        if (!$email) {
            $route = $intent === 'register' ? 'website.register' : 'owner.login';

            return redirect()->route($route)
                ->with('social_login_error', __('We could not retrieve your email from :provider. Please continue with email instead.', ['provider' => ucfirst($provider)]));
        }

        $email = strtolower(trim($email));
        $owner = TenantOwner::query()->where('email', $email)->first();

        if ($intent === 'login') {
            if (!$owner) {
                return redirect()->route('website.register')
                    ->with('social_login_error', __('No account was found for :email. Please register a new store.', ['email' => $email]));
            }

            return $this->loginOwner($owner);
        }

        // intent === 'register'
        if ($owner) {
            return $this->loginOwner($owner)->with('social_login_notice', __('You already have an account — signed you in.'));
        }

        $package = filled($packageId) ? Package::query()->find((int) $packageId) : null;

        if ($package && (float) $package->price > 0) {
            // Paid plan: hand off to the existing pending-session restore path so the
            // user still picks a payment gateway; email is already OAuth-verified.
            session([
                'website.register.pending' => [
                    'id' => (string) Str::uuid(),
                    'data' => [
                        'email' => $email,
                        'phone' => '',
                        'package_id' => $package->id,
                        'package_price' => (float) $package->price,
                        'gateway_code' => '',
                    ],
                ],
            ]);

            return redirect()->route('website.register');
        }

        // Free plan: email is already verified via OAuth, so skip the "check your
        // email" confirmation step and go straight to shop setup.
        $token = Str::random(64);

        PendingRegistration::create([
            'token' => $token,
            'email' => $email,
            'phone' => null,
            'locale' => app()->getLocale(),
            'package_id' => $package?->id,
            'affiliate_referral_id' => app(\App\Services\AffiliateService::class)->resolveReferral(request())?->id,
            'payment_data' => null,
            'expires_at' => now()->addHours(48),
        ]);

        session([
            'website.register.oauth' => [
                'email' => $email,
                'name' => $socialUser->getName() ?: $socialUser->getNickname() ?: Str::before($email, '@'),
                'provider' => $provider,
                'provider_id' => $socialUser->getId(),
                'avatar' => $socialUser->getAvatar(),
            ],
        ]);

        return redirect()->route('website.register.complete', ['token' => $token]);
    }

    private function popupSuccess(string $email, string $name, string $provider, bool $existingAccount = false, ?string $providerId = null, ?string $avatar = null): \Illuminate\Http\Response
    {
        $payload = json_encode([
            'type' => 'social_auth_success',
            'email' => $email,
            'name' => $name,
            'provider' => $provider,
            'provider_id' => $providerId,
            'avatar' => $avatar,
            'existing_account' => $existingAccount,
        ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);

        return response(view('auth.social-popup-result', [
            'payload' => $payload,
            'success' => true,
        ]));
    }

    private function popupError(string $message): \Illuminate\Http\Response
    {
        $payload = json_encode([
            'type' => 'social_auth_error',
            'message' => $message,
        ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);

        return response(view('auth.social-popup-result', [
            'payload' => $payload,
            'success' => false,
        ]));
    }

    private function loginOwner(TenantOwner $owner): RedirectResponse
    {
        Auth::guard('tenant_owner')->login($owner, true);
        request()->session()->regenerate();

        $tenants = $owner->availableTenants();

        if ($tenants->count() <= 1) {
            $tenant = $tenants->first();

            if ($tenant && $owner->selected_tenant_id !== $tenant->id) {
                $owner->forceFill(['selected_tenant_id' => $tenant->id])->save();
            }

            return redirect()->route('owner.dashboard');
        }

        return redirect()->route('owner.select-tenant');
    }
}
