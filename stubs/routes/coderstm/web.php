<?php

use Illuminate\Support\Facades\Route;
use Coderstm\Http\Controllers\Payment;

// Payments
Route::group(['prefix' => 'payment', 'as' => 'payment.'], function () {
    Route::get('gocardless/success', [Payment\GoCardlessController::class, 'success'])->name('gocardless.success');
    Route::get('stripe/success', [Payment\StripeController::class, 'success'])->name('stripe.success');
    Route::get('paypal/success', [Payment\PaypalController::class, 'success'])->name('paypal.success');
    Route::get('razorpay/success', [Payment\RazorpayController::class, 'success'])->name('razorpay.success');
});

// Admin Frontend
Route::group(['prefix' => 'admin'], function () {
    Route::get('{query?}', function () {
        return view('app');
    })->where('query', '.*');
});

// User Frontend
Route::group(['prefix' => 'user'], function () {
    Route::get('{query?}', function () {
        return view('app');
    })->where('query', '.*');
});
