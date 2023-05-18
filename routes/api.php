<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Core\LogController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Core\FileController;
use App\Http\Controllers\Core\TaskController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Core\AdminController;
use App\Http\Controllers\Core\GroupController;
use App\Http\Controllers\Core\EnquiryController;
use App\Http\Controllers\Core\ApplicationController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Subscription\SubscriptionController;
use App\Http\Controllers\Subscription\PaymentMethodController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Auth Routes
Route::prefix('auth/{guard?}')->group(function () {
    Route::controller(AuthController::class)->group(function () {
        Route::post('signup', 'signup')->name('users.signup');
        Route::post('login', 'login')->name('users.login');
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', 'logout')->name('users.logout');
            Route::post('update', 'update')->name('users.update');
            Route::post('change-password', 'password')->name('users.change-password');
            Route::post('me', 'me')->name('users.current');
            Route::post('update-parq', 'updateParq')->name('users.update-parq');
        });
    });
    Route::controller(ForgotPasswordController::class)->group(function () {
        Route::post('request-password', 'request')->name('users.request-password');
        Route::post('reset-password', 'reset')->name('users.reset-password');
    });
});

// Core Routes
Route::middleware(['auth:sanctum', 'guard:admins'])->group(function () {
    // Application Settings
    Route::controller(ApplicationController::class)->group(function () {
        Route::get('application/stats', 'stats')->name('application.stats');
        Route::get('application/settings/{key}', 'getSettings')->name('application.get-settings');
        Route::middleware('can:update,App\Models\AppSetting')->group(function () {
            Route::post('application/settings', 'updateSettings')->name('application.update-settings');
        });
    });

    // Tasks
    Route::controller(TaskController::class)->middleware('can:update,task')->group(function () {
        Route::post('tasks/{task}/reply', 'reply')->name('tasks.reply');
        Route::post('tasks/{task}/change-archived', 'changeArchived')->name('tasks.change-archived');
    });
    Route::apiResource('tasks', TaskController::class)->except([
        'update'
    ]);

    // Admins
    Route::controller(AdminController::class)->group(function () {
        Route::get('admins/options', 'options')->name('admins.options');
        Route::get('admins/modules', 'modules')->name('admins.modules');
        Route::middleware('can:update,admin')->group(function () {
            Route::post('admins/{admin}/reset-password-request', 'resetPasswordRequest')->name('admins.reset-password-request');
            Route::post('admins/{admin}/change-active', 'changeActive')->name('admins.change-active');
            Route::post('admins/{admin}/change-admin', 'changeAdmin')->name('admins.change-admin');
        });
    });
    Route::apiResource('admins', AdminController::class);

    // Groups
    Route::apiResource('groups', GroupController::class);

    // Logs
    Route::post('logs/{log}/reply', [LogController::class, 'reply'])->name('logs.reply');
    Route::apiResource('logs', LogController::class)->only([
        'show', 'update', 'destroy',
    ]);
});

// File Download
Route::get('files/{path}', [FileController::class, 'download'])->name('files.download');

Route::middleware(['auth:sanctum'])->group(function () {
    // Files
    Route::post('files/upload-from-source', [FileController::class, 'uploadFromSource'])->name('files.upload-from-source');
    Route::apiResource('files', FileController::class)->except([
        'destroySelected', 'restore', 'restoreSelected',
    ]);

    // Enquiries
    Route::controller(EnquiryController::class)->middleware('can:update,enquiry')->group(function () {
        Route::post('enquiries/{enquiry}/reply', 'reply')->name('enquiries.reply');
        Route::post('enquiries/{enquiry}/change-user-archived', 'changeUserArchived')->name('enquiries.change-user-archived');
        Route::post('enquiries/{enquiry}/change-archived', 'changeArchived')->name('enquiries.change-archived');
    });
    Route::apiResource('enquiries', EnquiryController::class);
});

// Common Routes
Route::middleware(['auth:sanctum'])->group(function () {
    // Subscription
    Route::prefix('subscription')->name('subscription.')->controller(SubscriptionController::class)->group(function () {
        Route::get('', 'index')->name('index');
        Route::get('setup-intent', 'getSetupIntent')->name('setup-intent');
        Route::post('subscribe', 'subscribe')->name('subscribe');
        Route::post('resume', 'resume')->name('resume');
        Route::post('confirm', 'confirm')->name('confirm');
        Route::post('invoices', 'invoices')->name('invoices');
        Route::get('invoices/{invoiceId}', 'downloadInvoice')->name('invoices.download');

        //Only for subscriber
        Route::middleware(['subscribed'])->group(function () {
            Route::post('cancel', 'cancel')->name('cancel');
        });
    });
    Route::prefix('payment-methods')->name('payment-methods.')->controller(PaymentMethodController::class)->group(function () {
        Route::post('{paymentMethod}/update', 'update')->name('update');
        Route::delete('{paymentMethod}', 'destroy')->name('destroy');
    });
    Route::resource('payment-methods', PaymentMethodController::class)->only([
        'index', 'store',
    ]);
});

// Admin Routes
Route::middleware(['auth:sanctum', 'guard:admins'])->group(function () {
    // Options
    Route::get('users/options', [UserController::class, 'options']);

    // Users
    Route::prefix('users')->name('users.')->group(function () {
        Route::controller(UserController::class)->group(function () {
            Route::get('enquiry', 'enquiry')->can('enquiry,App\Models\User')->name('enquiry');
            Route::middleware('can:update,user')->group(function () {
                Route::post('{user}/reset-password-request', 'resetPasswordRequest')->name('users.reset-password-request');
                Route::post('{user}/change-active', 'changeActive')->name('users.change-active');
                Route::post('{user}/notes', 'notes')->name('users.notes');
                Route::post('{user}/mark-as-paid', 'markAsPaid')->name('mark-as-paid');
            });
            Route::post('{user}/checked', [UserController::class, 'checked'])->can('admin,user')->name('users.checked');
        });
    });
    Route::apiResource('users', UserController::class);

    // Plans
    Route::middleware('can:update,plan')->group(function () {
        Route::post('plans/{plan}/change-active', [PlanController::class, 'changeActive'])->name('plans.change-active');
    });
    Route::apiResource('plans', PlanController::class);
});