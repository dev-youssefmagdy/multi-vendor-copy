<?php

namespace Database\Seeders;

use App\Enums\ActivationStatus;
use App\Enums\PaymentGatewayMode;
use App\Enums\PaymentGatewayType;
use DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Crypt;

class CentralPaymentGatewaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Each gateway is inserted twice — once for PaymentGatewayType::Orders
     * (customer → vendor storefront payments) and once for
     * PaymentGatewayType::VendorPayments (vendor → central payments such as
     * subscription renewals, order settlements, and manufacturing requests).
     *
     * The unique key is now (code, type) so both rows can coexist.
     */
    public function run(): void
    {
        //         'stripe'        => \App\PaymentGateway\Gateways\StripeGateway::class,
        // 'paypal'        => \App\PaymentGateway\Gateways\PayPalGateway::class,
        // 'mollie'        => \App\PaymentGateway\Gateways\MollieGateway::class,
        // 'razorpay'      => \App\PaymentGateway\Gateways\RazorpayGateway::class,
        // 'flutterwave'   => \App\PaymentGateway\Gateways\FlutterwaveGateway::class,
        // 'paystack'      => \App\PaymentGateway\Gateways\PaystackGateway::class,
        // 'authorize_net' => \App\PaymentGateway\Gateways\AuthorizeNetGateway::class,
        // 'instamojo'     => \App\PaymentGateway\Gateways\InstamojoGateway::class,
        // 'iyzico'        => \App\PaymentGateway\Gateways\IyzicoGateway::class,
        // 'midtrans'      => \App\PaymentGateway\Gateways\MidtransGateway::class,
        // 'mercadopago'   => \App\PaymentGateway\Gateways\MercadopagoGateway::class,
        // 'myfatoorah'    => \App\PaymentGateway\Gateways\MyFatoorahGateway::class,
        // 'paytabs'       => \App\PaymentGateway\Gateways\PaytabsGateway::class,
        // 'paytm'         => \App\PaymentGateway\Gateways\PaytmGateway::class,
        // 'perfect_money' => \App\PaymentGateway\Gateways\PerfectMoneyGateway::class,
        // 'phonepe'       => \App\PaymentGateway\Gateways\PhonePeGateway::class,
        // 'toyyibpay'     => \App\PaymentGateway\Gateways\ToyyibpayGateway::class,
        // 'xendit'        => \App\PaymentGateway\Gateways\XenditGateway::class,
        // 'yoco'          => \App\PaymentGateway\Gateways\YocoGateway::class,
        // '2checkout'     => \App\PaymentGateway\Gateways\TwoCheckoutGateway::class,

        $gateways = [
            [
                'name' => 'Stripe',
                'code' => 'stripe',
                'status' => ActivationStatus::Active->value,
                'mode' => PaymentGatewayMode::Test->value,
                'credentials' => [
                    'key' => '',
                    'secret' => '',
                    'sandbox' => true,
                ],
            ],
            [
                'name' => 'PayPal',
                'code' => 'paypal',
                'status' => ActivationStatus::Active->value,
                'mode' => PaymentGatewayMode::Test->value,
                'credentials' => [
                    'client_id' => '',
                    'client_secret' => '',
                    'sandbox' => true,
                ],
            ],
            [
                'name' => 'Mollie',
                'code' => 'mollie',
                'status' => ActivationStatus::Inactive->value,
                'mode' => PaymentGatewayMode::Test->value,
                'credentials' => [
                    'key' => '',
                    'sandbox' => true,
                ],
            ],
            [
                'name' => 'Razorpay',
                'code' => 'razorpay',
                'status' => ActivationStatus::Inactive->value,
                'mode' => PaymentGatewayMode::Test->value,
                'credentials' => [
                    'key' => '',
                    'secret' => '',
                    'sandbox' => true,
                ],
            ],
            [
                'name' => 'Flutterwave',
                'code' => 'flutterwave',
                'status' => ActivationStatus::Inactive->value,
                'mode' => PaymentGatewayMode::Test->value,
                'credentials' => [
                    'public_key' => '',
                    'secret_key' => '',
                    'sandbox' => true,
                ],
            ],
            [
                'name' => 'Paystack',
                'code' => 'paystack',
                'status' => ActivationStatus::Inactive->value,
                'mode' => PaymentGatewayMode::Test->value,
                'credentials' => [
                    'key' => '',
                    'email' => null,
                    'sandbox' => true,
                ],
            ],
            [
                'name' => 'Authorize.Net',
                'code' => 'authorize_net',
                'status' => ActivationStatus::Inactive->value,
                'mode' => PaymentGatewayMode::Test->value,
                'credentials' => [
                    'login_id' => '',
                    'transaction_key' => '',
                    'public_key' => '',
                    'sandbox' => true,
                ],
            ],
            [
                'name' => 'Instamojo',
                'code' => 'instamojo',
                'status' => ActivationStatus::Inactive->value,
                'mode' => PaymentGatewayMode::Test->value,
                'credentials' => [
                    'key' => '',
                    'token' => '',
                    'sandbox' => true,
                ],
            ],
            [
                'name' => 'Iyzico',
                'code' => 'iyzico',
                'status' => ActivationStatus::Inactive->value,
                'mode' => PaymentGatewayMode::Test->value,
                'credentials' => [
                    'api_key' => '',
                    'secret_key' => '',
                    'sandbox' => true,
                ],
            ],
            [
                'name' => 'Midtrans',
                'code' => 'midtrans',
                'status' => ActivationStatus::Inactive->value,
                'mode' => PaymentGatewayMode::Test->value,
                'credentials' => [
                    'server_key' => '',
                    'sandbox' => true,
                ],
            ],
            [
                'name' => 'MercadoPago',
                'code' => 'mercadopago',
                'status' => ActivationStatus::Inactive->value,
                'mode' => PaymentGatewayMode::Test->value,
                'credentials' => [
                    'token' => '',
                    'sandbox' => true,
                ],
            ],
            [
                'name' => 'MyFatoorah',
                'code' => 'myfatoorah',
                'status' => ActivationStatus::Inactive->value,
                'mode' => PaymentGatewayMode::Test->value,
                'credentials' => [
                    'token' => '',
                    'sandbox' => true,
                ],
            ],
            [
                'name' => 'PayTabs',
                'code' => 'paytabs',
                'status' => ActivationStatus::Inactive->value,
                'mode' => PaymentGatewayMode::Test->value,
                'credentials' => [
                    'profile_id' => '125178',
                    'server_key' => '',
                    'country' => 'global',
                    'sandbox' => true,
                ],
            ],
            [
                'name' => 'Paytm',
                'code' => 'paytm',
                'status' => ActivationStatus::Inactive->value,
                'mode' => PaymentGatewayMode::Test->value,
                'credentials' => [
                    'environment' => 'local',
                    'merchant' => '',
                    'secret' => '',
                    'sandbox' => true,
                    'website' => 'WEBSTAGING',
                    'industry' => 'Retail',
                ],
            ],
            [
                'name' => 'Perfect Money',
                'code' => 'perfect_money',
                'status' => ActivationStatus::Inactive->value,
                'mode' => PaymentGatewayMode::Test->value,
                'credentials' => [
                    'perfect_money_wallet_id' => '',
                    'sandbox' => true,
                ],
            ],
            [
                'name' => 'PhonePe',
                'code' => 'phonepe',
                'status' => ActivationStatus::Inactive->value,
                'mode' => PaymentGatewayMode::Test->value,
                'credentials' => [
                    'merchant_id' => '',
                    'salt_key' => '',
                    'sandbox' => true,
                ],
            ],
            [
                'name' => 'Toyyibpay',
                'code' => 'toyyibpay',
                'status' => ActivationStatus::Inactive->value,
                'mode' => PaymentGatewayMode::Test->value,
                'credentials' => [
                    'secret_key' => '',
                    'category_code' => '',
                    'sandbox' => true,
                ],
            ],
            [
                'name' => 'Xendit',
                'code' => 'xendit',
                'status' => ActivationStatus::Inactive->value,
                'mode' => PaymentGatewayMode::Test->value,
                'credentials' => [
                    'secret_key' => '',
                    'sandbox' => true,
                ],
            ],
            [
                'name' => 'Yoco',
                'code' => 'yoco',
                'status' => ActivationStatus::Inactive->value,
                'mode' => PaymentGatewayMode::Test->value,
                'credentials' => [
                    'secret_key' => '',
                    'sandbox' => true,
                ],
            ],
            [
                'name' => '2Checkout',
                'code' => '2checkout',
                'status' => ActivationStatus::Inactive->value,
                'mode' => PaymentGatewayMode::Test->value,
                'credentials' => [
                    'seller_id' => '',
                    'private_key' => '',
                    'secret_word' => '',
                    'sandbox' => true,
                ],
            ],
            [
                'name' => 'Checkout.com',
                'code' => 'checkout_com',
                'status' => ActivationStatus::Inactive->value,
                'mode' => PaymentGatewayMode::Test->value,
                'credentials' => [
                    'secret_key' => '',
                    'public_key' => '',
                    'processing_channel_id' => '',
                    'webhook_secret' => '',
                    'sandbox' => true,
                ],
            ],
            [
                'name' => 'Adyen',
                'code' => 'adyen',
                'status' => ActivationStatus::Inactive->value,
                'mode' => PaymentGatewayMode::Test->value,
                'credentials' => [
                    'api_key' => '',
                    'merchant_account' => '',
                    'client_key' => '',
                    'webhook_hmac_key' => '',
                    'sandbox' => true,
                ],
            ],
            [
                'name' => 'Moyasar',
                'code' => 'moyasar',
                'status' => ActivationStatus::Inactive->value,
                'mode' => PaymentGatewayMode::Test->value,
                'credentials' => [
                    'secret_key' => '',
                    'publishable_key' => '',
                    'webhook_secret' => '',
                    'sandbox' => true,
                ],
            ],
            [
                'name' => 'HyperPay',
                'code' => 'hyperpay',
                'status' => ActivationStatus::Inactive->value,
                'mode' => PaymentGatewayMode::Test->value,
                'credentials' => [
                    'access_token' => '',
                    'entity_id' => '',
                    'webhook_secret' => '',
                    'sandbox' => true,
                ],
            ],
            [
                'name' => 'Tap Payments',
                'code' => 'tap',
                'status' => ActivationStatus::Inactive->value,
                'mode' => PaymentGatewayMode::Test->value,
                'credentials' => [
                    'secret_key' => '',
                    'webhook_secret' => '',
                    'sandbox' => true,
                ],
            ],
            [
                'name' => 'Amazon Payment Services',
                'code' => 'amazon_payment_services',
                'status' => ActivationStatus::Inactive->value,
                'mode' => PaymentGatewayMode::Test->value,
                'credentials' => [
                    'access_code' => '',
                    'merchant_identifier' => '',
                    'sha_request_phrase' => '',
                    'sha_response_phrase' => '',
                    'sandbox' => true,
                ],
            ],
            [
                'name' => 'Paymob',
                'code' => 'paymob',
                'status' => ActivationStatus::Inactive->value,
                'mode' => PaymentGatewayMode::Test->value,
                'credentials' => [
                    'api_key' => '',
                    'integration_id' => '',
                    'iframe_id' => '',
                    'hmac_secret' => '',
                    'sandbox' => true,
                ],
            ],
            [
                'name' => 'Cashfree',
                'code' => 'cashfree',
                'status' => ActivationStatus::Inactive->value,
                'mode' => PaymentGatewayMode::Test->value,
                'credentials' => [
                    'app_id' => '',
                    'secret_key' => '',
                    'webhook_secret' => '',
                    'sandbox' => true,
                ],
            ],
        ];

        foreach ($gateways as $gateway) {
            $connection = DB::connection(config('tenancy.central_connection', config('database.default')));

            foreach ([PaymentGatewayType::Orders->value, PaymentGatewayType::VendorPayments->value] as $type) {
                $connection->table('payment_gateways')->updateOrInsert(
                    ['code' => $gateway['code'], 'type' => $type],
                    [
                        'name' => $gateway['name'],
                        'type' => $type,
                        'status' => $gateway['status'],
                        'mode' => $gateway['mode'],
                        'credentials' => Crypt::encryptString(json_encode($gateway['credentials'])),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }
}
