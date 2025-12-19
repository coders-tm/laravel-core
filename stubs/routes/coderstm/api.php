<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers as App;
use Coderstm\Http\Controllers as Coderstm;
use Coderstm\Http\Controllers\Auth;
use Coderstm\Http\Controllers\Subscription;
use Coderstm\Http\Controllers\Blog;
use Coderstm\Http\Controllers\Page;
use Coderstm\Http\Controllers\Webhook;
use Coderstm\Http\Controllers\Payment;
use Coderstm\Http\Controllers\User\WalletController;
use Coderstm\Http\Controllers\Admin\WalletController as AdminWalletController;

// Subscription Promo Code Check Route
Route::post('subscriptions/check-promo-code', [Subscription\SubscriptionController::class, 'checkPromoCode'])->name('subscriptions.check-promo-code');

// Auth Routes
Route::group([
    'as' => 'auth.',
    'prefix' => 'auth/{guard?}',
], function () {
    Route::controller(Auth\AuthController::class)->group(function () {
        Route::post('signup', 'signup')->name('signup');
        Route::post('login', 'login')->name('login');
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', 'logout')->name('logout');
            Route::post('update', 'update')->name('update');
            Route::post('change-password', 'password')->name('change-password');
            Route::post('me', 'me')->name('current');
            Route::post('request-account-deletion', 'requestAccountDeletion')->name('request-account-deletion');
            Route::post('add-device-token', 'addDeviceToken')->name('add-device-token');
        });
    });
    Route::group([
        'as' => 'password.',
        'controller' => Auth\ForgotPasswordController::class,
    ], function () {
        Route::post('request-password', 'request')->name('request');
        Route::post('reset-password', 'reset')->name('reset');
    });
});

// Core Routes
Route::middleware(['auth:sanctum', 'guard:admins'])->group(function () {
    // Notification templates
    Route::group([
        'as' => 'settings.notifications.',
        'prefix' => 'settings/notifications',
        'middleware' => 'can:update,Coderstm\Models\Notification',
        'controller' => Coderstm\NotificationController::class
    ], function () {
        Route::post('{notification}/mark-as-default', 'markAsDefault')->name('mark-as-default');
        Route::post('{notification}/duplicate', 'duplicate')->name('duplicate');
    });
    Route::apiResource('settings/notifications', Coderstm\NotificationController::class, [
        'as' => 'settings',
        'middleware' => 'can:update,Coderstm\Models\Notification',
        'only' => ['index', 'show', 'update', 'destroy']
    ]);

    // Application Settings
    Route::group([
        'as' => 'application.',
        'prefix' => 'application',
        'controller' => Coderstm\ApplicationController::class
    ], function () {
        Route::get('stats', 'stats')->name('stats');
        Route::get('editor-theme', 'theme')->name('editor-theme');
        Route::get('short-code', 'shortCode')->middleware('web')->name('short-code');
        Route::post('test-mail-config', 'testMailConfig')->name('test-mail-config');
        Route::get('settings/{key}', 'getSettings')->name('get-settings');
        Route::middleware('can:update,Coderstm\Models\AppSetting')->group(function () {
            Route::post('settings', 'updateSettings')->name('update-settings');
        });
    });

    // Tasks
    Route::group([
        'prefix' => 'tasks',
        'as' => 'tasks.',
        'controller' => Coderstm\TaskController::class,
        'middleware' => 'can:update,task'
    ], function () {
        Route::post('{task}/reply', 'reply')->name('reply');
        Route::post('{task}/change-archived', 'changeArchived')->name('change-archived');
    });
    Route::resource('tasks', Coderstm\TaskController::class)->except(['update']);

    // Admins
    Route::group([
        'as' => 'admins.',
        'prefix' => 'admins',
        'controller' => App\AdminController::class,
    ], function () {
        Route::get('options', 'options')->name('options');
        Route::post('import', 'import')->name('import');
        Route::get('modules', 'modules')->name('modules');
        Route::group(['middleware' => 'can:update,admin'], function () {
            Route::post('{admin}/reset-password-request', 'resetPasswordRequest')->name('reset-password-request');
            Route::post('{admin}/change-active', 'changeActive')->name('change-active');
            Route::post('{admin}/change-admin', 'changeAdmin')->name('change-admin');
        });
    });
    Route::resource('admins', App\AdminController::class);

    // Groups
    Route::resource('groups', Coderstm\GroupController::class);

    // Logs
    Route::post('logs/{log}/reply', [Coderstm\LogController::class, 'reply'])->name('logs.reply');
    Route::resource('logs', Coderstm\LogController::class)->only(['show', 'update', 'destroy']);

    // App Payment Methods
    Route::group([
        'controller' => Coderstm\PaymentMethodController::class,
        'as' => 'payment-methods.',
        'prefix' => 'payment-methods',
    ], function () {
        Route::post('{payment_method}/disable', 'disable')->name('disable');
        Route::post('{payment_method}/enable', 'enable')->name('enable');
    });
    Route::resource('payment-methods', Coderstm\PaymentMethodController::class)->only(['index', 'store', 'show', 'update']);
});

Route::middleware(['auth:sanctum'])->group(function () {
    // Files
    Route::post('files/upload-from-source', [Coderstm\FileController::class, 'uploadFromSource'])->name('files.upload-from-source');
    Route::resource('files', Coderstm\FileController::class)->except(['destroySelected', 'restore', 'restoreSelected']);

    // Enquiries
    Route::group([
        'controller' => App\EnquiryController::class,
        'middleware' => 'can:update,enquiry',
        'as' => 'enquiries.',
        'prefix' => 'enquiries',
    ], function () {
        Route::post('{enquiry}/reply', 'reply')->name('reply');
        Route::post('{enquiry}/change-user-archived', 'changeUserArchived')->name('change-user-archived');
        Route::post('{enquiry}/change-archived', 'changeArchived')->name('change-archived');
    });
    Route::resource('enquiries', App\EnquiryController::class);
});

// Common Routes
Route::middleware(['auth:sanctum'])->group(function () {
    // Subscription
    Route::group([
        'as' => 'subscriptions.',
        'prefix' => 'subscriptions',
    ], function () {
        Route::get('/', [Subscription\SubscriptionController::class, 'index'])->name('index');
        Route::get('/current', [Subscription\SubscriptionController::class, 'current'])->name('current');
        Route::post('/subscribe', [Subscription\SubscriptionController::class, 'subscribe'])->name('subscribe');
        Route::get('/{subscription}', [Subscription\SubscriptionController::class, 'show'])->name('show');
        Route::post('/{subscription}/resume', [Subscription\SubscriptionController::class, 'resume'])->name('resume');
        Route::post('/{subscription}/cancel-downgrade', [Subscription\SubscriptionController::class, 'cancelDowngrade'])->name('cancel-downgrade');
        Route::post('/{subscription}/cancel', [Subscription\SubscriptionController::class, 'cancel'])->name('cancel');
        Route::get('/{subscription}/invoices', [Subscription\SubscriptionController::class, 'invoices'])->name('invoices');
    });
    Route::group([
        'as' => 'invoices.',
        'prefix' => 'invoices',
    ], function () {
        Route::post('/', [Coderstm\InvoiceController::class, 'invoices']);
        Route::get('/{invoice}', [Coderstm\InvoiceController::class, 'downloadInvoice'])->name('download');
    });
});

// User Routes
Route::middleware(['auth:sanctum', 'guard:users'])->group(function () {
    // Wallet
    Route::group([
        'as' => 'user.wallet.',
        'prefix' => 'user/wallet',
        'controller' => WalletController::class,
    ], function () {
        Route::get('balance', 'balance')->name('balance');
        Route::get('transactions', 'transactions')->name('transactions');
    });
});

// Admin Routes
Route::middleware(['auth:sanctum', 'guard:admins'])->group(function () {
    // Reports & Analytics
    Route::group([
        'as' => 'reports.',
        'prefix' => 'reports',
    ], function () {
        // Dashboard & Overview
        Route::controller(Coderstm\Admin\ReportsController::class)->group(function () {
            Route::get('charts', 'charts')->name('charts');
            Route::get('metrics', 'metrics')->name('metrics');
            Route::get('kpis', 'kpis')->name('kpis');
            Route::post('clear-cache', 'clearCache')->name('clear-cache');
        });

        // Report Exports Management
        Route::group([
            'as' => 'exports.',
            'prefix' => 'exports',
            'controller' => Coderstm\Admin\ReportExportsController::class,
        ], function () {
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->name('store');
            Route::get('/data', 'data')->name('data');
            Route::get('/available', 'available')->name('available');
            Route::get('/metadata', 'metadata')->name('metadata');
            Route::post('cleanup', 'cleanup')->name('cleanup');
            Route::delete('destroy', 'destroyMultiple')->name('destroy-multiple');
            Route::get('{reportExport}', 'show')->name('show');
            Route::get('{reportExport}/download', 'download')->name('download');
            Route::delete('{reportExport}', 'destroy')->name('destroy');
            Route::post('{reportExport}/retry', 'retry')->name('retry');
        });
    });

    // Subscription
    Route::group([
        'as' => 'subscriptions.',
        'prefix' => 'subscriptions',
    ], function () {
        Route::post('/', [Subscription\SubscriptionController::class, 'store'])->name('store');
        Route::post('/{subscription}', [Subscription\SubscriptionController::class, 'update'])->name('update');
        Route::post('/{subscription}/pay', [Subscription\SubscriptionController::class, 'pay'])->name('pay');
    });

    // Users
    Route::group(['prefix' => 'users', 'as' => 'users.'], function () {
        Route::group(['controller' => App\UserController::class], function () {
            Route::post('options', 'options')->name('options');
            Route::post('import', 'import')->name('import');
            Route::post('{user}/change-active', 'changeActive')->name('change-active');
            Route::post('{user}/notes', 'notes')->name('notes');
            Route::post('{user}/mark-as-paid', 'markAsPaid')->name('mark-as-paid');
            Route::post('{user}/reset-password-request', 'resetPasswordRequest')->name('reset-password-request');
        });

        // User Wallet Management
        Route::group([
            'as' => 'wallet.',
            'prefix' => '{user}/wallet',
            'controller' => AdminWalletController::class,
        ], function () {
            Route::get('balance', 'balance')->name('balance');
            Route::get('transactions', 'transactions')->name('transactions');
            Route::post('credit', 'credit')->name('credit');
            Route::post('debit', 'debit')->name('debit');
        });
    });
    Route::resource('users', App\UserController::class);

    // Plans
    Route::group([
        'as' => 'plans.',
        'prefix' => 'plans',
        'middleware' => 'can:update,plan',
    ], function () {
        Route::post('{plan}/change-active', [Coderstm\PlanController::class, 'changeActive'])->name('change-active');
    });
    Route::resource('plans', Coderstm\PlanController::class);

    // Coupons
    Route::group([
        'as' => 'coupons.',
        'prefix' => 'coupons',
    ], function () {
        Route::post('{coupon}/change-active', [Subscription\CouponController::class, 'changeActive'])->name('change-active');
        Route::post('{coupon}/logs', [Subscription\CouponController::class, 'logs'])->name('logs');
        Route::get('products', [Subscription\CouponController::class, 'products'])->name('products');
        Route::get('plans', [Subscription\CouponController::class, 'plans'])->name('plans');
    });
    Route::resource('coupons', Subscription\CouponController::class);

    // Taxes
    Route::resource('taxes', Coderstm\TaxController::class)->only(['index', 'store', 'update', 'destroy']);

    // Blogs
    Route::group([
        'as' => 'blogs.',
        'prefix' => 'blogs',
    ], function () {
        Route::resource('tags', Blog\TagController::class, [
            'middleware' => [
                'can:create,Coderstm\Models\Blog',
                'can:update,Coderstm\Models\Blog',
            ]
        ])->only(['index', 'store']);

        Route::group([
            'middleware' => 'can:update,blog',
            'controller' => Coderstm\BlogController::class,
        ], function () {
            Route::post('{blog}/change-active', 'changeActive')->name('change-active');
            Route::post('{blog}/comments', 'comments')->name('comments');
        });
    });
    Route::resource('blogs', Coderstm\BlogController::class);

    // Pages
    Route::group([
        'as' => 'pages.',
        'prefix' => 'pages',
    ], function () {
        Route::group([
            'as' => 'templates.',
            'controller' => Page\TemplateController::class
        ], function () {
            Route::get('templates', 'index')->name('index');
            Route::post('templates', 'store')->name('store');
            Route::get('templates/{template}', 'show')->name('show');
            Route::delete('templates', 'destroy')->name('destroy');
        });

        Route::group([
            'as' => 'blocks.',
            'controller' => Page\BlockController::class
        ], function () {
            Route::get('blocks', 'index')->name('index');
            Route::post('blocks', 'store')->name('store');
        });

        Route::group([
            // 'middleware' => 'can:update,page',
            'controller' => Coderstm\PageController::class,
        ], function () {
            Route::post('{page}/change-active', 'changeActive')->name('change-active');
        });
    });
    Route::resource('pages', Coderstm\PageController::class)->middleware('preserve.json.whitespace');

    Route::group(['prefix' => 'themes', 'as' => 'themes.'], function () {
        Route::get('/', [Coderstm\ThemeController::class, 'index'])->name('index');
        Route::post('/{theme}/active', [Coderstm\ThemeController::class, 'activate'])->name('activate');
        Route::delete('/{theme}/destroy', [Coderstm\ThemeController::class, 'destroy'])->name('destroy');
        Route::post('/{theme}/clone', [Coderstm\ThemeController::class, 'clone'])->name('clone');
        Route::post('/{theme}/assets', [Coderstm\ThemeController::class, 'assetsUpload'])->name('assets');

        Route::group(['prefix' => '{theme}/files'], function () {
            Route::get('/', [Coderstm\ThemeController::class, 'getFiles'])->name('files.list');
            Route::post('/', [Coderstm\ThemeController::class, 'saveFile'])->name('files.save');
            Route::post('/create', [Coderstm\ThemeController::class, 'createFile'])->name('files.create');
            Route::get('/content', [Coderstm\ThemeController::class, 'getFileContent'])->name('files.content');
            Route::delete('/destroy', [Coderstm\ThemeController::class, 'destroyThemeFile'])->name('files.destroy');
        });
    });
});

Route::group(['prefix' => 'shared'], function () {
    Route::get('plans', [Coderstm\PlanController::class, 'shared'])->name('plans.shared');
    Route::get('plans/features', [Coderstm\PlanController::class, 'features'])->name('plans.features');
});

Route::group(['controller' => Coderstm\ApplicationController::class, 'prefix' => 'application', 'as' => 'application.'], function () {
    Route::get('config', 'config')->name('config');
    Route::get('payment-methods', 'paymentMethods')->name('payment-methods');
});

// Webhooks
Route::post('stripe/webhook', [Webhook\StripeController::class, 'handleWebhook'])->name('stripe.webhook');
Route::post('paypal/webhook', [Payment\PaypalController::class, 'webhook'])->name('paypal.webhook');
Route::post('razorpay/webhook', [Payment\RazorpayController::class, 'webhook'])->name('razorpay.webhook');
Route::post('gocardless/webhook', [Webhook\GoCardlessController::class, 'handleWebhook'])->name('gocardless.webhook');

// Payments
Route::group(['prefix' => 'payment', 'as' => 'payment.'], function () {
    Route::get('status/{token}', [Coderstm\PaymentController::class, 'status'])->name('status');
    Route::post('{provider}/setup-intent', [Coderstm\PaymentController::class, 'setupPaymentIntent'])->name('setup-intent');
    Route::post('{provider}/confirm', [Coderstm\PaymentController::class, 'confirmPayment'])->name('confirm');
});

Route::get('/themes/{theme}/assets', [Coderstm\ThemeController::class, 'assets'])->name('themes.assets.preview');
