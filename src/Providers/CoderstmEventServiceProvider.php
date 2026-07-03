<?php

namespace Coderstm\Providers;

use Coderstm\Events\EnquiryCreated;
use Coderstm\Events\EnquiryReplyCreated;
use Coderstm\Events\Shop\OrderRefunded;
use Coderstm\Events\Shop\PartialRefundProcessed;
use Coderstm\Events\Shop\PaymentFailed;
use Coderstm\Events\Shop\PaymentSuccessful;
use Coderstm\Events\TaskCreated;
use Coderstm\Events\UserSubscribed;
use Coderstm\Listeners\DeleteExpiredNotificationTokens;
use Coderstm\Listeners\SendEnquiryConfirmation;
use Coderstm\Listeners\SendEnquiryNotification;
use Coderstm\Listeners\SendEnquiryReplyNotification;
use Coderstm\Listeners\SendSignupNotification;
use Coderstm\Listeners\SendTaskUsersNotification;
use Coderstm\Listeners\Shop\SendAdminPaymentFailedNotification;
use Coderstm\Listeners\Shop\SendAdminRefundNotification;
use Coderstm\Listeners\Shop\SendOrderRefundedNotification;
use Coderstm\Listeners\Shop\SendPartialRefundNotification;
use Coderstm\Listeners\Shop\SendPaymentFailedNotification;
use Coderstm\Listeners\Shop\SendPaymentSuccessNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Notifications\Events\NotificationFailed;

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
        EnquiryReplyCreated::class => [
            SendEnquiryReplyNotification::class,
        ],
        TaskCreated::class => [
            SendTaskUsersNotification::class,
        ],
        UserSubscribed::class => [
            SendSignupNotification::class,
        ],
        NotificationFailed::class => [
            DeleteExpiredNotificationTokens::class,
        ],
        // Shop Payment Events
        PaymentSuccessful::class => [
            SendPaymentSuccessNotification::class,
        ],
        PaymentFailed::class => [
            SendPaymentFailedNotification::class,
            SendAdminPaymentFailedNotification::class,
        ],
        OrderRefunded::class => [
            SendOrderRefundedNotification::class,
            SendAdminRefundNotification::class,
        ],
        PartialRefundProcessed::class => [
            SendPartialRefundNotification::class,
            SendAdminRefundNotification::class,
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
