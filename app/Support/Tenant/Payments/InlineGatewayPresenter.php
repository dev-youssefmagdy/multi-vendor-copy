<?php

declare(strict_types=1);

namespace App\Support\Tenant\Payments;

use Illuminate\Support\Collection;

/**
 * Strips a raw gateway-credentials collection (as returned by
 * PaymentManager::vendorPaymentGateways() / centralGatewaysForPayment(),
 * which includes secret keys) down to the public/publishable fields that
 * are safe to send to the browser for inline card tokenisation.
 */
final class InlineGatewayPresenter
{
    private const INLINE_CODES = ['stripe', 'authorize_net', '2checkout'];

    /**
     * Whitelist of credential keys that may leave the server, per gateway.
     */
    private const PUBLIC_CRED_KEYS = [
        'stripe' => ['key'],
        'authorize_net' => ['login_id', 'client_key', 'transaction_key'],
        '2checkout' => ['seller_id'],
    ];

    public function present(Collection $gateways, ?string $selected = null): array
    {
        $presented = $gateways->map(function (array $gateway) {
            $isInline = in_array($gateway['code'], self::INLINE_CODES, true);

            return [
                'id' => $gateway['id'],
                'code' => $gateway['code'],
                'name' => $gateway['name'],
                'mode' => $gateway['mode'] ?? 'live',
                'is_inline' => $isInline,
                'creds' => $isInline ? $this->publicCreds($gateway['code'], (array) $gateway['creds']) : [],
            ];
        })->values();

        $inlineMap = $presented->filter(fn (array $g) => $g['is_inline'])->keyBy('code');

        $authNet = $inlineMap->get('authorize_net');

        return [
            'gateways' => $presented->all(),
            'selected' => $selected,
            'active_inline' => $selected ? ($inlineMap->get($selected)) : null,
            'sdk' => [
                'stripe' => $inlineMap->has('stripe'),
                'authorize_net' => $authNet ? (($authNet['mode'] ?? 'live') === 'test' ? 'sandbox' : 'live') : null,
                '2checkout' => $inlineMap->has('2checkout'),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function publicCreds(string $code, array $creds): array
    {
        $allowed = self::PUBLIC_CRED_KEYS[$code] ?? [];

        return collect($creds)
            ->only($allowed)
            ->map(fn ($value) => (string) $value)
            ->all();
    }
}
