<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\AdminUser;
use App\Services\Mail\TemplateMailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class EmailVerificationController extends Controller
{
    /**
     * "Resend verification email" (Account Setup sidebar / widget). Always sent through
     * the central (platform) mail configuration. The sidebar posts via AJAX
     * (data-tenant-form) and needs a JSON message to toast; a plain form post gets
     * the usual redirect back with a flash status.
     */
    public function send(Request $request, TemplateMailService $mailService): RedirectResponse|JsonResponse
    {
        /** @var AdminUser|null $admin */
        $admin = auth('tenant')->user();

        if (!$admin) {
            return redirect()->route('tenant.login');
        }

        if ($admin->hasVerifiedEmail()) {
            return $this->respond($request, true, __('Your email is already verified.'));
        }

        if (!filled($admin->email)) {
            return $this->respond($request, false, __('Add an email address to your account before verifying it.'));
        }

        $verifyUrl = URL::temporarySignedRoute(
            'tenant.verification.verify',
            now()->addMinutes(60),
            ['id' => $admin->id, 'hash' => sha1($admin->email)]
        );

        $sent = $mailService->sendTenantEmailVerification($admin->email, $verifyUrl);

        return $this->respond(
            $request,
            $sent,
            $sent
                ? __('A verification link has been sent to :email.', ['email' => $admin->email])
                : __('We could not send the verification email. Please try again later or contact support.')
        );
    }

    private function respond(Request $request, bool $ok, string $message): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            // 422 makes the panel's form handler toast the message as an error.
            return response()->json(['message' => $message], $ok ? 200 : 422);
        }

        return back()->with('status', $message)->with('status_type', $ok ? 'success' : 'error');
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
            ->with('status', __('Your email address has been verified.'))
            ->with('status_type', 'success');
    }
}
