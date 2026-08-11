<?php

namespace App\PaymentGateway;

use Illuminate\Support\Facades\Http;

/**
 * Performs a lightweight read-only API call for each gateway to verify
 * that the supplied credentials are valid and the account is reachable.
 *
 * ping() returns: ['ok' => bool, 'message' => string]
 */
class GatewayConnectionChecker
{
    public function ping(string $code, array $config): array
    {
        try {
            return match ($code) {
                'stripe' => $this->stripe($config),
                'paypal' => $this->paypal($config),
                'mollie' => $this->mollie($config),
                'razorpay' => $this->razorpay($config),
                'flutterwave' => $this->flutterwave($config),
                'paystack' => $this->paystack($config),
                'mercadopago' => $this->mercadopago($config),
                'myfatoorah' => $this->myfatoorah($config),
                'xendit' => $this->xendit($config),
                'yoco' => $this->yoco($config),
                'instamojo' => $this->instamojo($config),
                'authorize_net' => $this->authorizeNet($config),
                'midtrans' => $this->midtrans($config),
                'paytabs' => $this->paytabs($config),
                'toyyibpay' => $this->toyyibpay($config),
                default => $this->credentialPresenceCheck($config),
            };
        } catch (\Throwable $e) {
            return $this->fail('Error: ' . $e->getMessage());
        }
    }

    // ─── Gateway-specific pings ───────────────────────────────────────────────

    private function stripe(array $cfg): array
    {
        if (empty($cfg['secret'])) {
            return $this->fail('Secret key is missing.');
        }

        $response = Http::withToken($cfg['secret'])
            ->timeout(10)
            ->get('https://api.stripe.com/v1/balance');

        if ($response->successful()) {
            $responseGatewayMode = $response->json('livemode') ? 'live' : 'test';
            $isSame = $responseGatewayMode === ($cfg['sandbox'] ?? false ? 'test' : 'live');
            if (!$isSame) {
                return $this->fail('Stripe: The provided key does not match the selected mode.');
            }
        }

        if ($response->successful()) {
            $mode = ($cfg['sandbox'] ?? false) ? 'test' : 'live';
            return $this->ok("Connected to Stripe ({$mode} mode).");
        }

        $error = $response->json('error.message') ?? $response->body();
        return $this->fail('Stripe: ' . $error);
    }

    private function paypal(array $cfg): array
    {
        if (empty($cfg['client_id']) || empty($cfg['client_secret'])) {
            return $this->fail('Client ID or secret is missing.');
        }

        $base = ($cfg['sandbox'] ?? false)
            ? 'https://api-m.sandbox.paypal.com'
            : 'https://api-m.paypal.com';

        $response = Http::asForm()
            ->baseUrl($base)
            ->withBasicAuth($cfg['client_id'], $cfg['client_secret'])
            ->acceptJson()
            ->timeout(10)
            ->post('/v1/oauth2/token', ['grant_type' => 'client_credentials']);

        if ($response->successful() && $response->json('access_token')) {
            $mode = ($cfg['sandbox'] ?? false) ? 'sandbox' : 'live';
            return $this->ok("Connected to PayPal ({$mode} mode).");
        }

        $error = $response->json('error_description') ?? $response->json('error') ?? $response->body();
        return $this->fail('PayPal: ' . $error);
    }

    private function mollie(array $cfg): array
    {
        if (empty($cfg['key'])) {
            return $this->fail('API key is missing.');
        }

        $response = Http::withToken($cfg['key'])
            ->timeout(10)
            ->get('https://api.mollie.com/v2/me');

        if ($response->successful()) {
            $name = $response->json('name') ?? 'your account';
            return $this->ok("Connected to Mollie — {$name}.");
        }

        $error = $response->json('detail') ?? $response->json('title') ?? $response->body();
        return $this->fail('Mollie: ' . $error);
    }

    private function razorpay(array $cfg): array
    {
        if (empty($cfg['key']) || empty($cfg['secret'])) {
            return $this->fail('Key ID or secret is missing.');
        }

        $response = Http::withBasicAuth($cfg['key'], $cfg['secret'])
            ->timeout(10)
            ->get('https://api.razorpay.com/v1/payments', ['count' => 1]);

        // 200 = valid; 401 = bad creds; 400 = valid auth but bad params (still connected)
        if ($response->status() === 200 || $response->status() === 400) {
            return $this->ok('Connected to Razorpay.');
        }

        $error = $response->json('error.description') ?? $response->body();
        return $this->fail('Razorpay: ' . $error);
    }

    private function flutterwave(array $cfg): array
    {
        if (empty($cfg['secret_key'])) {
            return $this->fail('Secret key is missing.');
        }

        $response = Http::withToken($cfg['secret_key'])
            ->timeout(10)
            ->get('https://api.flutterwave.com/v3/banks/NG');

        if ($response->successful()) {
            return $this->ok('Connected to Flutterwave.');
        }

        $error = $response->json('message') ?? $response->body();
        return $this->fail('Flutterwave: ' . $error);
    }

    private function paystack(array $cfg): array
    {
        if (empty($cfg['secret_key'])) {
            return $this->fail('Secret key is missing.');
        }

        $response = Http::withToken($cfg['secret_key'])
            ->timeout(10)
            ->get('https://api.paystack.co/balance');

        if ($response->successful() && $response->json('status')) {
            return $this->ok('Connected to Paystack.');
        }

        $error = $response->json('message') ?? $response->body();
        return $this->fail('Paystack: ' . $error);
    }

    private function mercadopago(array $cfg): array
    {
        if (empty($cfg['access_token'])) {
            return $this->fail('Access token is missing.');
        }

        $response = Http::withToken($cfg['access_token'])
            ->timeout(10)
            ->get('https://api.mercadopago.com/v1/users/me');

        if ($response->successful()) {
            $email = $response->json('email') ?? '';
            return $this->ok('Connected to MercadoPago' . ($email ? " ({$email})" : '') . '.');
        }

        $error = $response->json('message') ?? $response->body();
        return $this->fail('MercadoPago: ' . $error);
    }

    private function myfatoorah(array $cfg): array
    {
        if (empty($cfg['token'])) {
            return $this->fail('API token is missing.');
        }

        $base = ($cfg['sandbox'] ?? false)
            ? 'https://apitest.myfatoorah.com'
            : 'https://api.myfatoorah.com';

        $response = Http::withToken($cfg['token'])
            ->timeout(10)
            ->get("{$base}/v2/GetCurrenciesExchangeList");

        if ($response->successful() && $response->json('IsSuccess')) {
            return $this->ok('Connected to MyFatoorah.');
        }

        $error = $response->json('ValidationErrors.0.Error') ?? $response->json('Message') ?? $response->body();
        return $this->fail('MyFatoorah: ' . $error);
    }

    private function xendit(array $cfg): array
    {
        if (empty($cfg['secret_key'])) {
            return $this->fail('Secret key is missing.');
        }

        $response = Http::withBasicAuth($cfg['secret_key'], '')
            ->timeout(10)
            ->get('https://api.xendit.co/balance');

        if ($response->successful()) {
            $balance = $response->json('balance') ?? '—';
            return $this->ok("Connected to Xendit (balance: {$balance}).");
        }

        $error = $response->json('message') ?? $response->body();
        return $this->fail('Xendit: ' . $error);
    }

    private function yoco(array $cfg): array
    {
        if (empty($cfg['secret_key'])) {
            return $this->fail('Secret key is missing.');
        }

        $response = Http::withToken($cfg['secret_key'])
            ->timeout(10)
            ->get('https://payments.yoco.com/api/checkouts', ['pageSize' => 1]);

        // 200 or 404 (no checkouts yet) both indicate valid credentials
        if ($response->status() === 200 || $response->status() === 404) {
            return $this->ok('Connected to Yoco.');
        }

        $error = $response->json('displayMessage') ?? $response->json('message') ?? $response->body();
        return $this->fail('Yoco: ' . $error);
    }

    private function instamojo(array $cfg): array
    {
        if (empty($cfg['api_key']) || empty($cfg['auth_token'])) {
            return $this->fail('API key or auth token is missing.');
        }

        $base = ($cfg['sandbox'] ?? false)
            ? 'https://test.instamojo.com/api/1.1'
            : 'https://www.instamojo.com/api/1.1';

        $response = Http::withHeaders([
            'X-Api-Key' => $cfg['api_key'],
            'X-Auth-Token' => $cfg['auth_token'],
        ])->timeout(10)->get("{$base}/accounts/");

        if ($response->successful() && $response->json('success')) {
            return $this->ok('Connected to Instamojo.');
        }

        $error = $response->json('message') ?? $response->body();
        return $this->fail('Instamojo: ' . $error);
    }

    private function authorizeNet(array $cfg): array
    {
        if (empty($cfg['login_id']) || empty($cfg['transaction_key'])) {
            return $this->fail('Login ID or transaction key is missing.');
        }

        $base = ($cfg['sandbox'] ?? false)
            ? 'https://apitest.authorize.net/xml/v1/request.api'
            : 'https://api.authorize.net/xml/v1/request.api';

        $response = Http::timeout(10)->post($base, [
            'authenticateTestRequest' => [
                'merchantAuthentication' => [
                    'name' => $cfg['login_id'],
                    'transactionKey' => $cfg['transaction_key'],
                ],
            ],
        ]);

        $resultCode = $response->json('authenticateTestResponse.messages.resultCode') ?? '';

        if ($resultCode === 'Ok') {
            return $this->ok('Connected to Authorize.Net.');
        }

        $error = $response->json('authenticateTestResponse.messages.message.0.text') ?? $response->body();
        return $this->fail('Authorize.Net: ' . $error);
    }

    private function midtrans(array $cfg): array
    {
        if (empty($cfg['server_key'])) {
            return $this->fail('Server key is missing.');
        }

        $base = ($cfg['sandbox'] ?? false)
            ? 'https://api.sandbox.midtrans.com'
            : 'https://api.midtrans.com';

        // Query a non-existent order — a 404 with valid JSON means auth passed
        $response = Http::withBasicAuth($cfg['server_key'], '')
            ->timeout(10)
            ->get("{$base}/v2/ping-check-connection/status");

        if ($response->status() === 200 || $response->status() === 404) {
            return $this->ok('Connected to Midtrans.');
        }

        $error = $response->json('status_message') ?? $response->body();
        return $this->fail('Midtrans: ' . $error);
    }

    private function paytabs(array $cfg): array
    {
        if (empty($cfg['profile_id']) || empty($cfg['server_key']) || empty($cfg['region_url'])) {
            return $this->fail('Profile ID, server key, or region URL is missing.');
        }

        $endpoint = rtrim($cfg['region_url'], '/') . '/payment/query';

        $response = Http::timeout(10)
            ->withHeaders(['Authorization' => $cfg['server_key']])
            ->post($endpoint, [
                'profile_id' => (int) $cfg['profile_id'],
                'tran_ref' => 'ping-test',
            ]);

        // PayTabs returns 400 with JSON on bad tran_ref — that still confirms auth
        if ($response->status() === 200 || $response->status() === 400) {
            $msg = $response->json('message') ?? '';
            if (str_contains(strtolower($msg), 'invalid') || str_contains(strtolower($msg), 'not found')) {
                return $this->ok('Connected to PayTabs.');
            }
            if ($response->status() === 200) {
                return $this->ok('Connected to PayTabs.');
            }
        }

        if ($response->status() === 401 || $response->status() === 403) {
            return $this->fail('PayTabs: Authentication failed — check server key.');
        }

        $error = $response->json('message') ?? $response->body();
        return $this->fail('PayTabs: ' . $error);
    }

    private function toyyibpay(array $cfg): array
    {
        if (empty($cfg['secret_key'])) {
            return $this->fail('Secret key is missing.');
        }

        $base = ($cfg['sandbox'] ?? false)
            ? 'https://dev.toyyibpay.com'
            : 'https://toyyibpay.com';

        $response = Http::asForm()
            ->timeout(10)
            ->post("{$base}/index.php/api/getUserByUserSecretKey", [
                'userSecretKey' => $cfg['secret_key'],
            ]);

        $body = $response->json();

        if (is_array($body) && !empty($body[0]['name'])) {
            return $this->ok('Connected to ToyyibPay — ' . $body[0]['name'] . '.');
        }

        $error = is_array($body) ? ($body[0]['status'] ?? $response->body()) : $response->body();
        return $this->fail('ToyyibPay: ' . $error);
    }

    // ─── Fallback for gateways without a simple ping endpoint ────────────────

    private function credentialPresenceCheck(array $config): array
    {
        $missing = collect($config)
            ->filter(fn($v, $k) => $k !== 'sandbox' && blank($v))
            ->keys()
            ->all();

        if (empty($missing)) {
            return $this->ok('All credentials are present. Live verification is not available for this gateway.');
        }

        return $this->fail('Missing credentials: ' . implode(', ', $missing));
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function ok(string $message): array
    {
        return ['ok' => true, 'message' => $message];
    }

    private function fail(string $message): array
    {
        return ['ok' => false, 'message' => $message];
    }
}
