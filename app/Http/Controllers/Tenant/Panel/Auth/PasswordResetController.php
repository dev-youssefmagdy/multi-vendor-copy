<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Auth;

use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Http\Requests\Tenant\Panel\Auth\ForgotPasswordRequest;
use App\Http\Requests\Tenant\Panel\Auth\ResetPasswordRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class PasswordResetController extends PanelController
{
    public function showForgot(): View
    {
        return view('tenant.pages.auth.forgot-password');
    }

    public function sendLink(ForgotPasswordRequest $request): JsonResponse
    {
        Password::broker('tenant_admins')->sendResetLink([
            'email' => strtolower(trim((string) $request->validated('email'))),
        ]);

        // Same answer whether or not the account exists, so emails cannot be enumerated.
        return $this->success('If an account exists for this email, a reset link has been sent.');
    }

    public function showReset(Request $request, string $token): View
    {
        return view('tenant.pages.auth.reset-password', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        $credentials = $request->validated();
        $credentials['email'] = strtolower(trim((string) $credentials['email']));

        $status = Password::broker('tenant_admins')->reset(
            $credentials,
            function ($admin, string $password): void {
                $admin->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => 'This reset link is invalid or has expired. Please request a new one.',
            ]);
        }

        return $this->success('Your password has been reset. You can sign in now.', redirect: route('tenant.login'));
    }
}
