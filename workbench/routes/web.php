<?php

use Illuminate\Support\Facades\Route;
use Workbench\App\Http\Controllers\PaymentController;

Route::get('/orders', [PaymentController::class, 'index'])->name('order-form');
Route::post('/orders/create', [PaymentController::class, 'createOrder'])->name('orders.create');

// Payment page for invoice payment
Route::get('/payment/{token?}', [PaymentController::class, 'showPaymentPage'])->name('payment');
