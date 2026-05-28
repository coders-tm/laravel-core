<?php

use Coderstm\Http\Controllers as Coderstm;
use Coderstm\Http\Controllers\Payment;
use Illuminate\Support\Facades\Route;

// Payments
Route::group(['prefix' => 'payment', 'as' => 'payment.'], function () {
    Route::get('gocardless/success', [Payment\GoCardlessController::class, 'success'])->name('gocardless.success');
    Route::get('{provider}/success', [Coderstm\PaymentController::class, 'handleSuccess'])->name('success');
    Route::get('{provider}/cancel', [Coderstm\PaymentController::class, 'handleCancel'])->name('cancel');
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
