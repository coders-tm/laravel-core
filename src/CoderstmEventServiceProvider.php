<?php

namespace Coderstm;

use Coderstm\Events\TaskCreated;
use Coderstm\Events\EnquiryCreated;
use Coderstm\Events\UserSubscribed;
use Laravel\Cashier\Events\WebhookReceived;
use Coderstm\Listeners\SendSignupNotification;
use Coderstm\Listeners\SendEnquiryConfirmation;
use Coderstm\Listeners\SendEnquiryNotification;
use Coderstm\Listeners\SendTaskUsersNotification;
use Coderstm\Events\Cashier\SubscriptionProcessed;
use Coderstm\Listeners\Cashier\CashierEventListener;
use Coderstm\Listeners\Cashier\SubscriptionEventListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class CoderstmEventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        EnquiryCreated::class => [
            SendEnquiryNotification::class,
            SendEnquiryConfirmation::class,
        ],
        TaskCreated::class => [
            SendTaskUsersNotification::class,
        ],
        WebhookReceived::class => [
            CashierEventListener::class,
        ],
        SubscriptionProcessed::class => [
            SubscriptionEventListener::class,
        ],
        UserSubscribed::class => [
            SendSignupNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
