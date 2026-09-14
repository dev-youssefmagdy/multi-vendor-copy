<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;
use Symfony\Component\HttpFoundation\Response;

class AdminAuthController extends Controller
{
    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    /**
     * Used by the admin panel's Echo client (see resources/js/bootstrap.js)
     * to authorize private/presence channels under the 'admin' guard.
     */
    public function broadcastAuth(Request $request): Response
    {
        return Broadcast::auth($request);
    }
}
