<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Payment Gateway
    |--------------------------------------------------------------------------
    | The gateway that will be used when none is explicitly specified.
    | Must match a key in the "drivers" array below.
    */
    'default' => env('PAYMENT_GATEWAY_DEFAULT', 'stripe'),

    /*
    |--------------------------------------------------------------------------
    | Driver Map
    |--------------------------------------------------------------------------
    | Maps each gateway key to its concrete implementation class.
    | Remove or comment out any gateways you do not need.
    */
    'drivers' => [
        'stripe' => \App\PaymentGateway\Gateways\StripeGateway::class,
        'paypal' => \App\PaymentGateway\Gateways\PayPalGateway::class,
        'mollie' => \App\PaymentGateway\Gateways\MollieGateway::class,
        'razorpay' => \App\PaymentGateway\Gateways\RazorpayGateway::class,
        'flutterwave' => \App\PaymentGateway\Gateways\FlutterwaveGateway::class,
        'paystack' => \App\PaymentGateway\Gateways\PaystackGateway::class,
        'authorize_net' => \App\PaymentGateway\Gateways\AuthorizeNetGateway::class,
        'instamojo' => \App\PaymentGateway\Gateways\InstamojoGateway::class,
        'iyzico' => \App\PaymentGateway\Gateways\IyzicoGateway::class,
        'midtrans' => \App\PaymentGateway\Gateways\MidtransGateway::class,
        'mercadopago' => \App\PaymentGateway\Gateways\MercadopagoGateway::class,
        'myfatoorah' => \App\PaymentGateway\Gateways\MyFatoorahGateway::class,
        'paytabs' => \App\PaymentGateway\Gateways\PaytabsGateway::class,
        'paytm' => \App\PaymentGateway\Gateways\PaytmGateway::class,
        'perfect_money' => \App\PaymentGateway\Gateways\PerfectMoneyGateway::class,
        'phonepe' => \App\PaymentGateway\Gateways\PhonePeGateway::class,
        'toyyibpay' => \App\PaymentGateway\Gateways\ToyyibpayGateway::class,
        'xendit' => \App\PaymentGateway\Gateways\XenditGateway::class,
        'yoco' => \App\PaymentGateway\Gateways\YocoGateway::class,
        '2checkout' => \App\PaymentGateway\Gateways\TwoCheckoutGateway::class,
        'checkout_com' => \App\PaymentGateway\Gateways\CheckoutComGateway::class,
        'adyen' => \App\PaymentGateway\Gateways\AdyenGateway::class,
        'moyasar' => \App\PaymentGateway\Gateways\MoyasarGateway::class,
        'hyperpay' => \App\PaymentGateway\Gateways\HyperPayGateway::class,
        'tap' => \App\PaymentGateway\Gateways\TapGateway::class,
        'amazon_payment_services' => \App\PaymentGateway\Gateways\AmazonPaymentServicesGateway::class,
        'paymob' => \App\PaymentGateway\Gateways\PaymobGateway::class,
        'cashfree' => \App\PaymentGateway\Gateways\CashfreeGateway::class,
    ],

];
