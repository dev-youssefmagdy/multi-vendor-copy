<?php

namespace App\PaymentGateway\Providers;

use App\PaymentGateway\PaymentManager;
use Illuminate\Support\ServiceProvider;

class PaymentGatewayServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Merge default config
        $this->mergeConfigFrom(
            app_path() . '/PaymentGateway/config/payment-gateways.php',
            'payment-gateways'
        );

        // Bind the manager as a singleton
        $this->app->singleton('payment.manager', fn() => new PaymentManager());
        $this->app->alias('payment.manager', PaymentManager::class);
    }

    public function boot(): void
    {
        // Allow publishing the config file
        $this->publishes([
            app_path() . '/PaymentGateway/config/payment-gateways.php' => config_path('payment-gateways.php'),
        ], 'payment-gateways-config');
    }
}
