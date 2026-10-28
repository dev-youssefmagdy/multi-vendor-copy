<?php

declare(strict_types=1);

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Customer;
use App\Services\Mobile\MobileTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Services\Mobile\MobilePasswordReset;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function __construct(private readonly MobileTokenService $tokens)
    {
    }

    public function register(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'name' => 'required|string|max:120',
            'email' => 'required|email|unique:customers,email',
            'phone' => 'nullable|string|max:30',
            'password' => 'required|min:8|confirmed',
            'device_name' => 'nullable|string|max:120',
        ]);
        if ($v->fails()) {
            return $this->invalid($v->errors()->toArray());
        }

        $customer = Customer::create([
            'full_name' => $request->input('name'),
            'email' => $request->input('email'),
            'phone' => (string) $request->input('phone', ''),
            'password' => Hash::make($request->input('password')),
            'active' => true,
            'language' => $request->header('Accept-Language') ? substr((string) $request->header('Accept-Language'), 0, 5) : null,
        ]);

        return response()->json(['success' => true, 'data' => $this->session($customer, $request)], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
            'device_name' => 'nullable|string|max:120',
        ]);
        if ($v->fails()) {
            return $this->invalid($v->errors()->toArray());
        }

        $customer = Customer::query()->where('email', $request->input('email'))->first();

        if (!$customer || !$customer->active || !Hash::check($request->input('password'), (string) $customer->password)) {
            return response()->json(['success' => false, 'message' => 'Invalid email or password.', 'code' => 'invalid_credentials'], 401);
        }

        return response()->json(['success' => true, 'data' => $this->session($customer, $request)]);
    }

    public function refresh(Request $request): JsonResponse
    {
        $new = $this->tokens->refresh((string) $request->input('refresh_token'));

        return $new
            ? response()->json(['success' => true, 'data' => $new])
            : response()->json(['success' => false, 'message' => 'Invalid refresh token.', 'code' => 'invalid_refresh_token'], 401);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->tokens->revoke($request->attributes->get('mobile_token'));

        return response()->json(['success' => true]);
    }

    /** Always 200 (no account enumeration). The reset token is e-mailed; delivery failures are logged only. */
    public function forgotPassword(Request $request, MobilePasswordReset $reset): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        if ($token = $reset->issue((string) $request->input('email'))) {
            try {
                Mail::raw(__('Your password reset code: :token (valid for :min minutes).', ['token' => $token, 'min' => MobilePasswordReset::TTL_MINUTES]),
                    fn ($m) => $m->to($request->input('email'))->subject(__('Reset your password')));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return response()->json(['success' => true, 'message' => 'If the email exists, reset instructions will be sent.']);
    }

    public function resetPassword(Request $request, MobilePasswordReset $reset): JsonResponse
    {
        $request->validate(['email' => 'required|email', 'token' => 'required|string', 'password' => 'required|min:8|confirmed']);

        return $reset->reset((string) $request->input('email'), (string) $request->input('token'), (string) $request->input('password'), $this->tokens)
            ? response()->json(['success' => true])
            : response()->json(['success' => false, 'message' => 'Invalid or expired reset code.', 'code' => 'invalid_reset_token'], 422);
    }

    public function deleteAccount(Request $request): JsonResponse
    {
        $customer = $request->user();
        if (!Hash::check((string) $request->input('password'), (string) $customer->password)) {
            return response()->json(['success' => false, 'message' => 'Password is incorrect.', 'code' => 'invalid_credentials'], 403);
        }

        $this->tokens->revokeAllFor($customer);
        // Keep order history intact: deactivate + anonymise credentials instead of hard delete.
        $customer->forceFill(['active' => false, 'email' => 'deleted-'.$customer->id.'-'.now()->timestamp.'@deleted.invalid', 'password' => Hash::make(bin2hex(random_bytes(16)))])->save();

        return response()->json(['success' => true]);
    }

    private function session(Customer $customer, Request $request): array
    {
        return $this->tokens->issue($customer, $request->input('device_name', $request->header('X-Platform')))
            + ['customer' => ['id' => $customer->id, 'name' => $customer->full_name, 'email' => $customer->email, 'phone' => $customer->phone]];
    }

    private function invalid(array $errors): JsonResponse
    {
        return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $errors], 422);
    }
}
