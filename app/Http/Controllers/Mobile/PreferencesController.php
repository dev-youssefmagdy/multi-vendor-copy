<?php

declare(strict_types=1);

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Tenant\MobileDeviceToken;
use App\Repositories\Tenant\StorefrontRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Locale / currency / country choices. The server is stateless about them: the app stores its choice
 * and sends Accept-Language, X-Currency and X-Country on every request (see ResolveMobileTenant).
 */
class PreferencesController extends Controller
{
    public function options(StorefrontRepository $repo): JsonResponse
    {
        return response()->json(['success' => true, 'data' => [
            'locales' => $repo->activeLanguages()->map(fn ($l) => [
                'code' => $l->code, 'name' => $l->native_name ?: $l->name, 'rtl' => $l->direction === 'rtl', 'default' => (bool) $l->is_default,
            ])->values(),
            'currencies' => $repo->activeCurrencies()->map(fn ($c) => [
                'code' => $c->code, 'symbol' => $c->symbol, 'rate' => (float) $c->conversion_rate, 'default' => (bool) $c->is_default,
            ])->values(),
            'countries' => Country::query()->orderBy('name')->get(['id', 'iso2', 'name', 'phone_code', 'flag_emoji']),
            'current' => [
                'locale' => app()->getLocale(),
                'currency' => $repo->currentCurrency()?->code,
                'country_id' => session('storefront_country_id'),
            ],
        ]]);
    }

    /** POST /device-tokens (auth) : stored only, no push is sent from the backend. */
    public function storeDeviceToken(Request $request): JsonResponse
    {
        $v = $request->validate(['token' => 'required|string|max:512', 'platform' => 'nullable|string|max:20']);
        MobileDeviceToken::query()->updateOrCreate(['token' => $v['token']], [
            'customer_id' => $request->user()->id,
            'platform' => $v['platform'] ?? $request->header('X-Platform'),
            'app_version' => $request->header('X-App-Version'),
        ]);

        return response()->json(['success' => true], 201);
    }

    public function deleteDeviceToken(Request $request): JsonResponse
    {
        $v = $request->validate(['token' => 'required|string|max:512']);
        MobileDeviceToken::query()->where('token', $v['token'])->where('customer_id', $request->user()->id)->delete();

        return response()->json(['success' => true]);
    }
}
