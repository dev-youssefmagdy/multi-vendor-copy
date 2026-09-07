<?php

namespace App\PaymentGateway\Gateways;

use App\PaymentGateway\DTOs\PaymentCharge;
use App\PaymentGateway\DTOs\PaymentResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Iyzipay\Model\Locale;
use Iyzipay\Model\Currency;
use Iyzipay\Model\PaymentGroup;
use Iyzipay\Model\Buyer;
use Iyzipay\Model\Address;
use Iyzipay\Model\BasketItem;
use Iyzipay\Model\BasketItemType;
use Iyzipay\Options;
use Iyzipay\Request\CreatePayWithIyzicoInitializeRequest;
use Iyzipay\Request\RetrievePayWithIyzicoRequest;

/**
 * Iyzico Gateway
 * Composer: composer require iyzico/iyzipay-php
 * API Docs: https://dev.iyzipay.com/
 */
class IyzicoGateway extends AbstractPaymentGateway
{
    private Options $options;

    public function getKey(): string
    {
        return 'iyzico';
    }

    protected function boot(): void
    {
        $this->requireConfig(['api_key', 'secret_key']);

        $this->options = new Options();
        $this->options->setApiKey($this->cfg('api_key'));
        $this->options->setSecretKey($this->cfg('secret_key'));
        $this->options->setBaseUrl(
            $this->isSandbox()
                ? 'https://sandbox-api.iyzipay.com'
                : 'https://api.iyzipay.com'
        );
    }

    public function charge(PaymentCharge $charge): PaymentResult
    {
        $conversationId = uniqid('iyz_');
        $basketId       = 'B' . uniqid();

        $iyzRequest = new CreatePayWithIyzicoInitializeRequest();
        $iyzRequest->setLocale(Locale::EN);
        $iyzRequest->setConversationId($conversationId);
        $iyzRequest->setPrice((string) $charge->amount);
        $iyzRequest->setPaidPrice((string) $charge->amount);
        $iyzRequest->setCurrency(Currency::TL);    // Iyzico primarily supports TRY
        $iyzRequest->setBasketId($basketId);
        $iyzRequest->setPaymentGroup(PaymentGroup::PRODUCT);
        $iyzRequest->setCallbackUrl($charge->successUrl);
        $iyzRequest->setEnabledInstallments([1, 2, 3, 6, 9]);

        $buyer = new Buyer();
        $buyer->setId(uniqid('buyer_'));
        $buyer->setName($charge->name ?? 'Customer');
        $buyer->setSurname($charge->name ?? 'Customer');
        $buyer->setGsmNumber($charge->phone ?? '+905350000000');
        $buyer->setEmail($charge->email ?? 'noreply@example.com');
        $buyer->setIdentityNumber(request()->input('identity_number', '74300864791'));
        $buyer->setRegistrationAddress(request()->input('address', 'N/A'));
        $buyer->setIp(request()->ip());
        $buyer->setCity(request()->input('city', 'Istanbul'));
        $buyer->setCountry(request()->input('country', 'Turkey'));
        $iyzRequest->setBuyer($buyer);

        $shippingAddress = new Address();
        $shippingAddress->setContactName($charge->name ?? 'Customer');
        $shippingAddress->setCity(request()->input('city', 'Istanbul'));
        $shippingAddress->setCountry(request()->input('country', 'Turkey'));
        $shippingAddress->setAddress(request()->input('address', 'N/A'));
        $iyzRequest->setShippingAddress($shippingAddress);
        $iyzRequest->setBillingAddress($shippingAddress);

        $basketItem = new BasketItem();
        $basketItem->setId($charge->orderId ?? uniqid());
        $basketItem->setName($charge->description);
        $basketItem->setCategory1('Services');
        $basketItem->setItemType(BasketItemType::VIRTUAL);
        $basketItem->setPrice((string) $charge->amount);
        $iyzRequest->setBasketItems([$basketItem]);

        $checkoutFormInitialize = \Iyzipay\Model\CheckoutFormInitialize::create($iyzRequest, $this->options);

        if ($checkoutFormInitialize->getStatus() === 'success') {
            Session::put('iyzico_token', $checkoutFormInitialize->getToken());
            return PaymentResult::view('payments.iyzico', [
                'form'      => $checkoutFormInitialize->getCheckoutFormContent(),
                'token'     => $checkoutFormInitialize->getToken(),
                'cancel_url' => $charge->cancelUrl,
            ]);
        }

        return PaymentResult::failure($checkoutFormInitialize->getErrorMessage() ?? 'Iyzico initialisation failed.');
    }

    public function verify(Request $request): PaymentResult
    {
        $token = $request->input('token') ?? Session::get('iyzico_token');

        if (!$token) {
            return PaymentResult::failure('Missing Iyzico token in callback.');
        }

        $retrieveRequest = new RetrievePayWithIyzicoRequest();
        $retrieveRequest->setLocale(Locale::EN);
        $retrieveRequest->setToken($token);

        $checkoutForm = \Iyzipay\Model\CheckoutForm::retrieve($retrieveRequest, $this->options);

        if ($checkoutForm->getStatus() === 'success' && $checkoutForm->getPaymentStatus() === 'SUCCESS') {
            Session::forget('iyzico_token');
            return PaymentResult::success($checkoutForm->getPaymentId(), json_encode($checkoutForm->getRawResult()));
        }

        return PaymentResult::failure('Iyzico payment failed: ' . $checkoutForm->getErrorMessage());
    }
}
