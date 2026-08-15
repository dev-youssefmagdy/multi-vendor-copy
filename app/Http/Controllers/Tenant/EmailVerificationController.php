<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\AdminUser;
use App\Services\Mail\TemplateMailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class EmailVerificationController extends Controller
{
    public function send(Request $request, TemplateMailService $mailService): RedirectResponse
    {
        /** @var AdminUser|null $admin */
        $admin = auth('tenant')->user();

        if (!$admin) {
            return redirect()->route('tenant.login');
        }

        if ($admin->hasVerifiedEmail()) {
            return back()->with('status', 'Your email is already verified.')->with('status_type', 'success');
        }

        $verifyUrl = URL::temporarySignedRoute(
            'tenant.verification.verify',
            now()->addMinutes(60),
            ['id' => $admin->id, 'hash' => sha1($admin->email)]
        );

        $sent = $mailService->sendTenantEmailVerification($admin->email, $verifyUrl);

        return back()->with(
            'status',
            $sent ? 'A verification link has been sent to ' . $admin->email . '.' : 'We could not send the verification email. Please check your mail configuration.'
        )->with('status_type', $sent ? 'success' : 'error');
    }

    public function verify(Request $request, string $id, string $hash): RedirectResponse
    {
        $admin = AdminUser::query()->find($id);

        abort_if(!$admin || !hash_equals(sha1($admin->email), $hash), 403);

        if (!$admin->hasVerifiedEmail()) {
            $admin->markEmailAsVerified();
        }

        $redirectRoute = auth('tenant')->check() ? 'tenant.dashboard' : 'tenant.login';

        return redirect()->route($redirectRoute)
            ->with('status', 'Your email address has been verified.')
            ->with('status_type', 'success');
    }
}
