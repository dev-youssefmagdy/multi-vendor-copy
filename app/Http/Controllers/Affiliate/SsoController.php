<?php

namespace App\Http\Controllers\Affiliate;

use App\Http\Controllers\Controller;
use App\Models\AffiliateLoginToken;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class SsoController extends Controller
{
    public function __invoke(string $token): RedirectResponse
    {
        $hash = hash('sha256', $token);

        // Atomically burn the token: only one request can flip used_at from null.
        $burned = AffiliateLoginToken::query()
            ->where('token_hash', $hash)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->update(['used_at' => now()]);

        $affiliate = $burned
            ? AffiliateLoginToken::query()->where('token_hash', $hash)->first()?->affiliate
            : null;

        if (!$affiliate || !$affiliate->isActive()) {
            return redirect()->route('affiliate.login')
                ->withErrors(['email' => __('This sign-in link is invalid or has expired.')]);
        }

        Auth::guard('affiliate')->login($affiliate);
        request()->session()->regenerate();

        return redirect()->route('affiliate.dashboard');
    }
}
