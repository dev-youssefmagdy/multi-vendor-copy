<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Shell;

use App\Http\Controllers\Controller;
use App\Support\Tenant\PanelLocale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Header language switcher of the vendor panel. */
final class LocaleController extends Controller
{
    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        abort_unless(PanelLocale::isSupported($locale), 404);

        app()->setLocale($locale);

        return redirect()->back(fallback: route('tenant.dashboard'))
            ->withCookie(cookie()->forever(PanelLocale::COOKIE, $locale));
    }
}
