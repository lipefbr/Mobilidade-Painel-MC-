<?php

use Illuminate\Support\Facades\Session;
use App\Http\Controllers\PdfGeneratorController;
use App\Http\Controllers\Web\CredpayController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

/*
 * These routes use the root namespace 'App\Http\Controllers\Web'.
 */
Route::namespace('Web')->group(function () {
    // All the folder-based web routes
    include_route_files('web');

    Route::get('/', 'FrontPageController@index')->name('index');
    Route::get('/driverpage', 'FrontPageController@driverp')->name('driverpage');
    Route::get('/howdriving', 'FrontPageController@howdrive')->name('howdriving');
    Route::get('/driverrequirements', 'FrontPageController@driverrequirement')->name('driverrequirements');
    Route::get('/safety', 'FrontPageController@safetypage')->name('safety');
    Route::get('/serviceareas', 'FrontPageController@serviceareaspage')->name('serviceareas');
    Route::get('/compliance', 'FrontPageController@complaincepage')->name('complaince');
    Route::get('/privacy', 'FrontPageController@privacypage')->name('privacy');
    Route::get('/terms', 'FrontPageController@termspage')->name('terms');
    Route::get('/dmv', 'FrontPageController@dmvpage')->name('dmv');
    Route::get('/contactus', 'FrontPageController@contactuspage')->name('contactus');
    Route::post('/contactussendmail', 'FrontPageController@contactussendmailadd')->name('contactussendmail');

    Route::get('mercadopago-checkout', function () {
        return view('mercadopago.checkout');
    });

    Route::get('sadad-checkout', function () {
        return view('sadad.checkout');
    });

    Route::get('get-country-data', 'FrontPageController@country_code');
    Route::post('flutter-wave', 'MercadopagoController@flutterWaveSuceess');

    // Payment routes
    Route::get('payment', 'FrontPageController@payment');

    // PayPal routes
    Route::get('paypal', 'PayPalController@index')->name('paypal');
    Route::post('paypal/payment', 'PayPalController@payment')->name('paypal.payment');
    Route::get('paypal/payment/success', 'PayPalController@paymentSuccess')->name('paypal.payment.success');
    Route::get('paypal/payment/cancel', 'PayPalController@paymentCancel')->name('paypal.payment/cancel');

    // Stripe routes
    Route::get('stripe', 'StripeController@stripe');
    Route::post('stripe-checkout', 'StripeController@stripeCheckout')->name('checkout.process');
    Route::get('stripe-checkout-success', 'StripeController@stripeCheckoutSuccess')->name('checkout.success');
    Route::get('stripe-checkout-error', 'StripeController@stripeCheckoutError')->name('checkout.failure');

    // Flutterwave routes
    Route::get('flutterwave', 'FlutterwaveController@index');
    Route::get('flutterwave/payment/success', 'FlutterwaveController@flutterwaveCheckout')->name('flutterwave.success');

    // Paystack routes
    Route::get('paystack', 'PaystackController@index');
    Route::get('paystack/payment/success', 'PaystackController@paystackCheckout')->name('paystack.success');

    // Khalti routes
    Route::get('khalti', 'KhaltiController@index');
    Route::post('khalti/checkout', 'KhaltiController@khaltiCheckoutsuccess')->name('khalti.success');

    // Razorpay routes
    Route::get('/razorpay', 'RazorPayController@razorpay');
    Route::get('/payment-success', 'RazorPayController@razorpay_success')->name('razorpay.success');

    // Mercadopago routes
    Route::get('mercadopago', 'MercadopagoController@mercadepago');
    Route::get('mercadopago/payment/success', 'MercadopagoController@mercadopagoCheckout')->name('mercadopago.success');

    // Ccavenue routes
    Route::get('ccavenue', 'CcavenueController@index');
    Route::post('ccavenue/checkout', 'CcavenueController@ccavenueCheckout')->name('ccavenue.checkout');
    Route::get('ccavenue/payment/success', 'CcavenueController@success')->name('ccavenue.payment.response');
    Route::get('ccavenue/payment/failure', 'CcavenueController@failure')->name('ccavenue.payment.cancel');

    // Cashfree routes
    Route::get('cashfree', 'CashfreeController@create')->name('callback');
    Route::post('cashfree/payments/store', 'CashfreeController@store')->name('store');
    Route::any('cashfree/payments/success', 'CashfreeController@success')->name('cashfree.success');

    Route::view("success", 'success');
    Route::view("failure", 'failure');
    Route::view("pending", 'pending');

    // Thawani Pay routes
    Route::get('thawani-pay', 'ThawaniPayController@checkout')->name('checkout');
    Route::get('thawani-pay-success', 'ThawaniPayController@success')->name('thawani-pay-success');
    Route::get('thawani-pay-cancel', 'ThawaniPayController@cancel')->name('thawani-pay-cancel');

    // Credpay routes
    Route::get('/credpay', 'CredpayController@credpay'); // Exibe o formulário de checkout
    Route::post('/credpay/process', 'CredpayController@processPayment')->name('credpay.process'); // Processa o pagamento
});

Route::namespace('Web')->group(function () {
    Route::namespace('Admin')->group(function () {
        Route::get('html/{request_detail}', 'RequestController@EmailCustomerIvoiceDirect');
        Route::post('api/html', 'RequestController@generatePDF');
        Route::get('api/html/{id}', 'RequestController@EmailCustomerIvoiceDirect1');

        Route::post('/save-pdf', 'RequestController@savePdf');
        Route::post('/send-email', 'RequestController@sendEmail');
    });
});

// Rota para exibir erros de pagamento
Route::get('/payment-error', function () {
    return view('errors.payment');
})->name('payment.error');