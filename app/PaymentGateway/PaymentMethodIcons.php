<?php

namespace App\PaymentGateway;

/**
 * Maps payment_method keys from gateway meta() to display label + icon.
 */
class PaymentMethodIcons
{
    /**
     * Returns [{key, label, icon}] for an array of payment method keys.
     * Unknown keys are rendered as a generic badge.
     */
    public static function resolve(array $methods): array
    {
        $map = self::map();
        $result = [];
        foreach ($methods as $m) {
            $result[] = array_merge(['key' => $m], $map[$m] ?? [
                'label' => str_replace('_', ' ', ucfirst($m)),
                'icon' => 'generic',
            ]);
        }
        return $result;
    }

    public static function map(): array
    {
        return [
            'card'            => ['label' => 'Card',         'icon' => 'card'],
            'visa'            => ['label' => 'Visa',         'icon' => 'visa'],
            'mastercard'      => ['label' => 'Mastercard',   'icon' => 'mastercard'],
            'amex'            => ['label' => 'Amex',         'icon' => 'amex'],
            'apple_pay'       => ['label' => 'Apple Pay',    'icon' => 'apple_pay'],
            'google_pay'      => ['label' => 'Google Pay',   'icon' => 'google_pay'],
            'mada'            => ['label' => 'Mada',         'icon' => 'mada'],
            'stc_pay'         => ['label' => 'STC Pay',      'icon' => 'stc_pay'],
            'knet'            => ['label' => 'KNET',         'icon' => 'knet'],
            'benefit'         => ['label' => 'Benefit',      'icon' => 'benefit'],
            'upi'             => ['label' => 'UPI',          'icon' => 'upi'],
            'pix'             => ['label' => 'Pix',          'icon' => 'pix'],
            'paypal'          => ['label' => 'PayPal',       'icon' => 'paypal'],
            'paypal_balance'  => ['label' => 'PayPal',       'icon' => 'paypal'],
            'wallet'          => ['label' => 'Wallet',       'icon' => 'wallet'],
            'wallets'         => ['label' => 'Wallets',      'icon' => 'wallet'],
            'ewallet'         => ['label' => 'eWallet',      'icon' => 'wallet'],
            'mobile_money'    => ['label' => 'Mobile Money', 'icon' => 'mobile_money'],
            'mobile_wallet'   => ['label' => 'Mobile Wallet', 'icon' => 'mobile_money'],
            'bank_transfer'   => ['label' => 'Bank Transfer', 'icon' => 'bank'],
            'netbanking'      => ['label' => 'Net Banking',  'icon' => 'bank'],
            'fpx'             => ['label' => 'FPX',          'icon' => 'bank'],
            'ideal'           => ['label' => 'iDEAL',        'icon' => 'bank'],
            'sepa'            => ['label' => 'SEPA',         'icon' => 'bank'],
            'bancontact'      => ['label' => 'Bancontact',   'icon' => 'bank'],
            'klarna'          => ['label' => 'Klarna',       'icon' => 'klarna'],
            'bnpl'            => ['label' => 'Buy Now Pay Later', 'icon' => 'bnpl'],
            'qr_code'         => ['label' => 'QR Code',      'icon' => 'qr'],
            'qris'            => ['label' => 'QRIS',         'icon' => 'qr'],
            'ussd'            => ['label' => 'USSD',         'icon' => 'generic'],
            'kiosk'           => ['label' => 'Kiosk',        'icon' => 'generic'],
            'ticket'          => ['label' => 'Ticket',       'icon' => 'generic'],
            'voucher'         => ['label' => 'Voucher',      'icon' => 'generic'],
            'echeck'          => ['label' => 'eCheck',       'icon' => 'bank'],
            'eft'             => ['label' => 'EFT',          'icon' => 'bank'],
            'gopay'           => ['label' => 'GoPay',        'icon' => 'wallet'],
            'paytm_wallet'    => ['label' => 'Paytm',        'icon' => 'wallet'],
            'perfect_money_wallet' => ['label' => 'Perfect Money', 'icon' => 'wallet'],
        ];
    }
}
