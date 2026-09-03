<?php

namespace App\PaymentGateway\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \App\PaymentGateway\Contracts\PaymentGatewayInterface gateway(string $key)
 * @method static \App\PaymentGateway\Contracts\PaymentGatewayInterface fromRequest(?\Illuminate\Http\Request $request = null)
 * @method static string[] available()
 *
 * @see \App\PaymentGateway\PaymentManager
 */
class Payment extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'payment.manager';
    }
}
