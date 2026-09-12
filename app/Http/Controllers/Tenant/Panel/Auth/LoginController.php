<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Auth;

use App\Enums\ActivationStatus;
use App\Enums\TenantStatus;
use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Http\Requests\Tenant\Panel\Auth\LoginRequest;
use App\Models\Tenant\AdminUser;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class LoginController extends PanelController
{
    public function show(): View|RedirectResponse
    {
        if (Auth::guard('tenant')->check()) {
            return redirect()->route('tenant.dashboard');
        }

        return view('tenant.pages.auth.login');
    }

    public function validateLogin(LoginRequest $request): JsonResponse
    {
        return $this->validFormResponse();
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $tenant = tenant();
        /** @var AdminUser|null $admin */
        $admin = AdminUser::query()->with('role')->where('email', strtolower(trim((string) $validated['email'])))->first();

        if (!$tenant || !$admin || !Hash::check($validated['password'], (string) $admin->password)) {
            throw ValidationException::withMessages([
                'email' => 'The provided tenant admin credentials are invalid for this domain.',
            ]);
        }

        if (!in_array($tenant->status, [TenantStatus::Active, TenantStatus::Onboarding], true)) {
            throw ValidationException::withMessages([
                'email' => 'This tenant account is not allowed to access the vendor panel.',
            ]);
        }

        if ($admin->status !== ActivationStatus::Active) {
            throw ValidationException::withMessages([
                'email' => 'This tenant admin account is inactive.',
            ]);
        }

        Auth::guard('tenant')->login($admin, (bool) $validated['remember']);
        $admin->forceFill(['last_login_at' => now()])->save();
        $request->session()->regenerate();

        return $this->success(
            'Welcome back, '.$admin->name.'.',
            redirect: redirect()->intended(route('tenant.dashboard'))->getTargetUrl(),
        );
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('tenant')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $this->success('You have been signed out.', redirect: route('tenant.login'));
    }
}
