<?php

use Coderstm\Http\Controllers as Coderstm;
use Illuminate\Support\Facades\Route;

// Payments
Route::group(['prefix' => 'payment', 'as' => 'payment.'], function () {
    Route::match(['get', 'post'], '{provider}/success', [Coderstm\PaymentController::class, 'handleSuccess'])->name('success');
    Route::match(['get', 'post'], '{provider}/cancel', [Coderstm\PaymentController::class, 'handleCancel'])->name('cancel');
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
