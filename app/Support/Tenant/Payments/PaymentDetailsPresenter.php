<?php

declare(strict_types=1);

namespace App\Support\Tenant\Payments;

/**
 * Turns an order's stored `payment_details` ({transaction_id, gateway, raw, note})
 * into a gateway-agnostic, human-readable summary for the order details page.
 *
 * `raw` is whatever the gateway returned (usually a JSON string) so its shape
 * differs per gateway. Each field is resolved from an ordered list of candidate
 * paths: gateway-specific paths first, then generic ones that cover most
 * gateways. Unknown gateways therefore degrade to the generic lookup and, at
 * worst, to the raw payload only. Display-only: nothing is written back.
 */
final class PaymentDetailsPresenter
{
    /** Gateways that report amounts in minor units (cents, halalas, paise). */
    private const MINOR_UNIT_GATEWAYS = ['stripe', 'moyasar', 'checkout_com', 'razorpay', 'adyen'];

    private const GATEWAY_LABELS = [
        'paypal' => 'PayPal', 'myfatoorah' => 'MyFatoorah', 'hyperpay' => 'HyperPay', 'paytabs' => 'PayTabs',
        'phonepe' => 'PhonePe', 'toyyibpay' => 'ToyyibPay', 'checkout_com' => 'Checkout.com', '2checkout' => '2Checkout',
        'authorize_net' => 'Authorize.Net', 'mercadopago' => 'Mercado Pago', 'amazon_payment_services' => 'Amazon Payment Services',
        'perfect_money' => 'Perfect Money', 'paymob' => 'Paymob', 'iyzico' => 'iyzico',
    ];

    private const ZERO_DECIMAL_CURRENCIES = ['JPY', 'KRW', 'VND', 'CLP', 'XOF', 'XAF', 'UGX', 'RWF'];

    private const SENSITIVE_KEYS = [
        'password', 'secret', 'token', 'signature', 'authorization', 'api_key', 'client_secret',
        'card_number', 'cvv', 'cvc', 'pan',
    ];

    /** Candidate payload paths shared by most gateways, in priority order. */
    private const GENERIC = [
        'status' => ['status', 'state', 'payment_status', 'Status', 'InvoiceStatus', 'data.status', 'result.status', 'resultCode', 'transaction_status'],
        'amount' => ['amount', 'amount_captured', 'amount_paid', 'total', 'InvoiceValue', 'amount.value', 'payment.amount', 'transactions.0.amount.total', 'order_amount', 'paid_amount'],
        'currency' => ['currency', 'currency_code', 'Currency', 'amount.currency', 'amount.currency_code', 'transactions.0.amount.currency', 'order_currency'],
        'method' => ['payment_method_details.type', 'payment_method', 'paymentMethod', 'payment_type', 'channel', 'source.payment_method', 'source.type', 'PaymentGateway', 'payment_group', 'payer.payment_method'],
        'card_brand' => ['payment_method_details.card.brand', 'source.brand', 'source.company', 'card.brand', 'card.scheme', 'authorization.card.brand', 'card_type', 'cardType', 'scheme', 'card.network'],
        'card_last4' => ['payment_method_details.card.last4', 'source.last4', 'source.last_four', 'card.last4', 'card.last_four', 'last4', 'card.last4Digits', 'card_last4', 'masked_pan', 'source.number'],
        'card_expiry' => ['payment_method_details.card.exp_month', 'card.exp_month', 'source.expiry_month'],
        'payer_name' => ['billing_details.name', 'customer.name', 'payer.name.given_name', 'CustomerName', 'customer_name', 'cardholder_name', 'payer.payer_info.first_name', 'name'],
        'payer_email' => ['billing_details.email', 'receipt_email', 'customer.email', 'payer.email_address', 'payer.payer_info.email', 'CustomerEmail', 'customer_email', 'email'],
        'paid_at' => ['created', 'paid_at', 'paidAt', 'created_at', 'create_time', 'update_time', 'CreatedDate', 'transaction_date', 'payment_time', 'date'],
        'receipt_url' => ['receipt_url', 'transaction_url', 'links.receipt'],
        'failure' => ['failure_message', 'message', 'gateway_response', 'error.message', 'ResponseMessage', 'response_message', 'refusalReason'],
        'reference' => ['id', 'reference', 'tran_ref', 'payment_id', 'pspReference', 'InvoiceId', 'invoice_id', 'tx_ref', 'RefNo', 'cf_payment_id', 'order_id'],
        'refunded' => ['amount_refunded', 'refunded_amount', 'amount_refunded_total'],
    ];

    /** Gateway-specific candidates, tried before GENERIC. */
    private const OVERRIDES = [
        'stripe' => [
            'method' => ['payment_method_details.type'],
            'receipt_url' => ['receipt_url'],
            'failure' => ['failure_message', 'outcome.seller_message'],
            'reference' => ['id', 'payment_intent'],
        ],
        'paypal' => [
            'status' => ['state', 'status'],
            'amount' => ['transactions.0.amount.total', 'amount.value'],
            'payer_name' => ['payer.payer_info.first_name', 'payer.name.given_name'],
            'method' => ['payer.payment_method'],
        ],
        'myfatoorah' => [
            'status' => ['InvoiceStatus'],
            'amount' => ['InvoiceValue'],
            'currency' => ['InvoiceTransactions.0.Currency', 'Currency'],
            'method' => ['InvoiceTransactions.0.PaymentGateway', 'PaymentGateway'],
            'reference' => ['InvoiceTransactions.0.PaymentId', 'InvoiceId'],
            'payer_name' => ['CustomerName'],
            'payer_email' => ['CustomerEmail'],
            'card_last4' => ['InvoiceTransactions.0.CardNumber'],
        ],
        'flutterwave' => [
            'reference' => ['flw_ref', 'tx_ref', 'id'],
            'method' => ['payment_type'],
            'card_brand' => ['card.type'],
            'card_last4' => ['card.last_4digits'],
            'payer_email' => ['customer.email'],
            'payer_name' => ['customer.name'],
        ],
        'paystack' => [
            'method' => ['channel'],
            'card_brand' => ['authorization.card_type'],
            'card_last4' => ['authorization.last4'],
            'payer_email' => ['customer.email'],
            'failure' => ['gateway_response'],
            'reference' => ['reference', 'id'],
        ],
        'tap' => [
            'method' => ['source.payment_method', 'source.type'],
            'card_brand' => ['card.brand', 'source.payment_method'],
            'card_last4' => ['card.last_four'],
            'payer_name' => ['customer.first_name'],
            'payer_email' => ['customer.email'],
            'failure' => ['response.message'],
        ],
        'moyasar' => [
            'method' => ['source.type'],
            'card_brand' => ['source.company'],
            'card_last4' => ['source.number'],
            'payer_name' => ['source.name'],
            'failure' => ['source.message'],
        ],
        'paytabs' => [
            'status' => ['payment_result.response_status'],
            'failure' => ['payment_result.response_message'],
            'method' => ['payment_info.payment_method'],
            'card_brand' => ['payment_info.card_scheme'],
            'card_last4' => ['payment_info.payment_description'],
            'amount' => ['cart_amount', 'tran_total'],
            'currency' => ['cart_currency', 'tran_currency'],
            'reference' => ['tran_ref'],
        ],
        'paymob' => [
            'status' => ['obj.success', 'success'],
            'amount' => ['obj.amount_cents', 'amount_cents'],
            'currency' => ['obj.currency', 'currency'],
            'method' => ['obj.source_data.type', 'source_data.type'],
            'card_brand' => ['obj.source_data.sub_type', 'source_data.sub_type'],
            'card_last4' => ['obj.source_data.pan', 'source_data.pan'],
            'reference' => ['obj.id', 'id'],
        ],
        'razorpay' => [
            'method' => ['method'],
            'card_brand' => ['card.network'],
            'card_last4' => ['card.last4'],
            'payer_email' => ['email'],
        ],
        'adyen' => [
            'status' => ['resultCode'],
            'amount' => ['amount.value'],
            'method' => ['paymentMethod.type', 'additionalData.paymentMethod'],
            'card_brand' => ['additionalData.cardPaymentMethod', 'paymentMethod.brand'],
            'card_last4' => ['additionalData.cardSummary'],
            'reference' => ['pspReference'],
            'failure' => ['refusalReason'],
        ],
        'checkout_com' => [
            'method' => ['source.type'],
            'card_brand' => ['source.scheme'],
            'card_last4' => ['source.last4'],
            'payer_email' => ['customer.email'],
            'reference' => ['id', 'action_id'],
            'failure' => ['response_summary'],
        ],
        'hyperpay' => [
            'status' => ['result.description'],
            'method' => ['paymentBrand', 'paymentType'],
            'card_last4' => ['card.last4Digits'],
            'card_brand' => ['paymentBrand'],
            'reference' => ['id'],
        ],
        'xendit' => [
            'method' => ['payment_method', 'payment_channel'],
            'reference' => ['id', 'external_id'],
            'payer_email' => ['payer_email'],
        ],
        'cashfree' => [
            'method' => ['payment_group', 'payment_method.card.channel'],
            'card_last4' => ['payment_method.card.card_number'],
            'card_brand' => ['payment_method.card.card_network'],
            'amount' => ['payment_amount', 'order_amount'],
            'currency' => ['payment_currency'],
            'status' => ['payment_status'],
            'paid_at' => ['payment_time'],
            'reference' => ['cf_payment_id'],
            'failure' => ['payment_message'],
        ],
        'mollie' => [
            'amount' => ['amount.value'],
            'currency' => ['amount.currency'],
            'method' => ['method'],
            'paid_at' => ['paidAt', 'createdAt'],
            'receipt_url' => ['_links.checkout.href'],
        ],
        'yoco' => [
            'amount' => ['totalAmount', 'amount'],
            'method' => ['paymentMethod'],
            'reference' => ['paymentId', 'id'],
        ],
        'iyzico' => [
            'status' => ['paymentStatus', 'status'],
            'amount' => ['paidPrice', 'price'],
            'method' => ['paymentGroup', 'cardAssociation'],
            'card_brand' => ['cardAssociation'],
            'card_last4' => ['lastFourDigits'],
            'reference' => ['paymentId'],
            'failure' => ['errorMessage'],
        ],
        'midtrans' => [
            'status' => ['transaction_status'],
            'amount' => ['gross_amount'],
            'method' => ['payment_type'],
            'card_last4' => ['masked_card'],
            'card_brand' => ['card_type'],
            'reference' => ['transaction_id', 'order_id'],
            'paid_at' => ['settlement_time', 'transaction_time'],
            'failure' => ['status_message'],
        ],
        '2checkout' => [
            'status' => ['Status', 'ORDERSTATUS'],
            'amount' => ['GrossPrice', 'Price'],
            'currency' => ['Currency'],
            'method' => ['PaymentDetails.Type'],
            'card_last4' => ['PaymentDetails.PaymentMethod.LastDigits'],
            'card_brand' => ['PaymentDetails.PaymentMethod.CardType'],
            'reference' => ['RefNo'],
        ],
    ];

    /**
     * @param  array<string, mixed>|null  $details  Order::payment_details
     * @return array{
     *     has_data:bool, gateway:?string, gateway_label:string, status:?array{label:string,tone:string},
     *     headline:array<int, array{label:string,value:string,mono?:bool,href?:string}>,
     *     note:?string, raw:mixed
     * }
     */
    public function present(?array $details, ?string $fallbackGateway = null): array
    {
        $details = is_array($details) ? $details : [];
        $gateway = $this->normalizeCode((string) ($details['gateway'] ?? $fallbackGateway ?? ''));
        $raw = $this->decodeRaw($details['raw'] ?? null);
        $get = fn (string $field) => $this->resolve($raw, $gateway, $field);

        $currency = $this->scalar($get('currency'));
        $amount = $this->formatAmount($get('amount'), $currency, $gateway);
        $refunded = $this->formatAmount($get('refunded'), $currency, $gateway);
        $status = $this->status($get('status'));
        $failure = $this->scalar($get('failure'));

        $card = trim(implode(' ', array_filter([
            $this->headline($this->scalar($get('card_brand'))),
            $this->maskedLast4($this->scalar($get('card_last4'))),
        ])));

        $receipt = $this->scalar($get('receipt_url'));
        $reference = $details['transaction_id'] ?? $this->scalar($get('reference'));

        $headline = array_values(array_filter([
            $this->row('Transaction ID', $this->scalar($reference), mono: true),
            $this->row('Amount', $amount),
            $this->row('Payment method', $this->headline($this->scalar($get('method')))),
            $this->row('Card', $card),
            $this->row('Paid at', $this->formatDate($get('paid_at'))),
            $this->row('Payer', $this->scalar($get('payer_name'))),
            $this->row('Payer email', $this->scalar($get('payer_email'))),
            $refunded && $refunded !== $this->formatAmount(0, $currency, $gateway)
                ? $this->row('Refunded', $refunded) : null,
            $this->row('Gateway message', $status && $status['tone'] === 'success' ? null : $failure),
            $receipt && str_starts_with($receipt, 'http') ? $this->row('Receipt', 'View receipt', href: $receipt) : null,
        ]));

        return [
            'has_data' => $details !== [],
            'gateway' => $gateway ?: null,
            'gateway_label' => $gateway ? (self::GATEWAY_LABELS[$gateway] ?? $this->headline($gateway)) : '-',
            'status' => $status,
            'headline' => $headline,
            'note' => $this->scalar($details['note'] ?? null),
            'raw' => $this->redact($raw),
        ];
    }

    private function resolve(mixed $raw, string $gateway, string $field): mixed
    {
        if (!is_array($raw)) {
            return null;
        }

        $paths = array_merge(self::OVERRIDES[$gateway][$field] ?? [], self::GENERIC[$field] ?? []);

        foreach ($paths as $path) {
            $value = data_get($raw, $path);
            if ($value !== null && $value !== '' && !is_array($value)) {
                return $value;
            }
        }

        return null;
    }

    private function decodeRaw(mixed $raw): mixed
    {
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);

            return json_last_error() === JSON_ERROR_NONE && is_array($decoded) ? $decoded : $raw;
        }

        return $raw;
    }

    private function normalizeCode(string $code): string
    {
        return strtolower(str_replace(['-', ' '], '_', trim($code)));
    }

    private function scalar(mixed $value): ?string
    {
        if ($value === null || is_array($value) || is_object($value)) {
            return null;
        }
        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function headline(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        // Only prettify snake/kebab/lowercase codes; leave already-formatted values (e.g. "VISA/MASTER") intact.
        return preg_match('/^[a-z0-9_\- ]+$/', $value)
            ? str($value)->replace(['_', '-'], ' ')->headline()->toString()
            : $value;
    }

    private function maskedLast4(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $digits = preg_replace('/\D/', '', $value) ?? '';

        return strlen($digits) >= 4 ? '•••• ' . substr($digits, -4) : null;
    }

    private function formatAmount(mixed $value, ?string $currency, string $gateway): ?string
    {
        if ($value === null || $value === '' || !is_numeric($value)) {
            return null;
        }

        $number = (float) $value;
        $code = $currency ? strtoupper($currency) : null;

        if (in_array($gateway, self::MINOR_UNIT_GATEWAYS, true) && !in_array($code, self::ZERO_DECIMAL_CURRENCIES, true)) {
            $number /= 100;
        }

        return trim(number_format($number, 2) . ' ' . ($code ?? ''));
    }

    private function formatDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            $date = is_numeric($value)
                ? \Illuminate\Support\Carbon::createFromTimestamp((int) $value)
                : \Illuminate\Support\Carbon::parse((string) $value);
        } catch (\Throwable) {
            return null;
        }

        return $date->year >= 2000 ? $date->format('M d, Y H:i') : null;
    }

    /** @return array{label:string,tone:string}|null */
    private function status(mixed $value): ?array
    {
        if ($value === null || $value === '' || is_array($value)) {
            return null;
        }

        $label = is_bool($value) ? ($value ? 'Paid' : 'Failed') : (string) $value;
        $key = strtolower($label);

        $tone = match (true) {
            in_array($key, ['succeeded', 'success', 'successful', 'paid', 'approved', 'completed', 'captured', 'settlement', 'authorised', 'authorized', 'capture', '1', 'true'], true) => 'success',
            in_array($key, ['failed', 'failure', 'declined', 'cancelled', 'canceled', 'expired', 'refused', 'error', 'denied', 'deny', '0', 'false'], true) => 'danger',
            in_array($key, ['pending', 'processing', 'initiated', 'requires_action', 'in_progress', 'authorizing'], true) => 'warning',
            default => 'neutral',
        };

        return ['label' => $this->headline($label) ?? $label, 'tone' => $tone];
    }

    private function row(string $label, ?string $value, bool $mono = false, ?string $href = null): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        return array_filter(['label' => $label, 'value' => $value, 'mono' => $mono ?: null, 'href' => $href]);
    }

    private function redact(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        $out = [];
        foreach ($value as $key => $item) {
            $lower = strtolower((string) $key);
            $sensitive = collect(self::SENSITIVE_KEYS)->contains(fn ($needle) => $lower === $needle || str_ends_with($lower, '_' . $needle));
            $out[$key] = $sensitive && !is_array($item) ? '••••••' : $this->redact($item);
        }

        return $out;
    }
}
