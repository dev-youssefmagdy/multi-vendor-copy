<?php

use App\Http\Controllers\Payment\UnifiedPaymentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Payment Gateway Routes
|--------------------------------------------------------------------------
| Include this file from routes/web.php:
|
|   require base_path('routes/payment.php');
|
| Or add it to the list in RouteServiceProvider.php:
|
|   $this->mapPaymentRoutes();
|
|   protected function mapPaymentRoutes(): void
|   {
|       Route::middleware('web')
|            ->group(base_path('routes/payment.php'));
|   }
|--------------------------------------------------------------------------
*/

Route::prefix('payment')->name('payment.')->group(function () {

    /*
     * Initiate payment — POST to this URL with amount, currency, etc.
     * The {gateway} segment selects the strategy (e.g. "stripe", "paypal").
     */
    Route::post('{gateway}/charge', [UnifiedPaymentController::class, 'charge'])
        ->name('charge');

    /*
     * Success / callback — gateways redirect here after approval.
     * Some gateways use GET, some use POST.
     */
    Route::match(['get', 'post'], '{gateway}/success', [UnifiedPaymentController::class, 'success'])
        ->name('success');

    /*
     * Cancel — gateways redirect here when the user cancels.
     */
    Route::get('{gateway}/cancel', [UnifiedPaymentController::class, 'cancel'])
        ->name('cancel');

    /*
     * Terminal pages — replace with your own views / controller actions.
     */
    Route::get('confirmed', fn() => view('payments.confirmed'))->name('confirmed');
    Route::get('cancelled', fn() => view('payments.cancelled'))->name('cancelled');

});
