<?php

namespace Coderstm\Providers;

use Coderstm\Events\Checkout\Abandoned;
use Coderstm\Events\EnquiryCreated;
use Coderstm\Events\EnquiryReplyCreated;
use Coderstm\Events\Shop\LowStockAlert;
use Coderstm\Events\Shop\OrderCanceled;
use Coderstm\Events\Shop\OrderDelivered;
use Coderstm\Events\Shop\OrderPaid;
use Coderstm\Events\Shop\OrderRefunded;
use Coderstm\Events\Shop\OrderShipped;
use Coderstm\Events\Shop\OutOfStockAlert;
use Coderstm\Events\Shop\PartialRefundProcessed;
use Coderstm\Events\Shop\PaymentFailed;
use Coderstm\Events\Shop\PaymentSuccessful;
use Coderstm\Events\SubscriptionCancelled;
use Coderstm\Events\SubscriptionPlanChanged;
use Coderstm\Events\TaskCreated;
use Coderstm\Events\UserSubscribed;
use Coderstm\Listeners\DeleteExpiredNotificationTokens;
use Coderstm\Listeners\GoCardless\SubscriptionCancelledListener;
use Coderstm\Listeners\GoCardless\SubscriptionChangeListener;
use Coderstm\Listeners\SendEnquiryConfirmation;
use Coderstm\Listeners\SendEnquiryNotification;
use Coderstm\Listeners\SendEnquiryReplyNotification;
use Coderstm\Listeners\SendSignupNotification;
use Coderstm\Listeners\SendTaskUsersNotification;
use Coderstm\Listeners\Shop\SendAbandonedCartNotification;
use Coderstm\Listeners\Shop\SendAdminNewOrderNotification;
use Coderstm\Listeners\Shop\SendAdminOrderCanceledNotification;
use Coderstm\Listeners\Shop\SendAdminPaymentFailedNotification;
use Coderstm\Listeners\Shop\SendAdminRefundNotification;
use Coderstm\Listeners\Shop\SendLowStockNotification;
use Coderstm\Listeners\Shop\SendOrderCanceledNotification;
use Coderstm\Listeners\Shop\SendOrderConfirmationNotification;
use Coderstm\Listeners\Shop\SendOrderDeliveredNotification;
use Coderstm\Listeners\Shop\SendOrderRefundedNotification;
use Coderstm\Listeners\Shop\SendOrderShippedNotification;
use Coderstm\Listeners\Shop\SendOutOfStockNotification;
use Coderstm\Listeners\Shop\SendPartialRefundNotification;
use Coderstm\Listeners\Shop\SendPaymentFailedNotification;
use Coderstm\Listeners\Shop\SendPaymentSuccessNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Notifications\Events\NotificationFailed;

class CoderstmEventServiceProvider extends ServiceProvider
{
    protected $listen = [EnquiryCreated::class => [SendEnquiryNotification::class, SendEnquiryConfirmation::class], EnquiryReplyCreated::class => [SendEnquiryReplyNotification::class], TaskCreated::class => [SendTaskUsersNotification::class], UserSubscribed::class => [SendSignupNotification::class], NotificationFailed::class => [DeleteExpiredNotificationTokens::class], SubscriptionPlanChanged::class => [SubscriptionChangeListener::class], SubscriptionCancelled::class => [SubscriptionCancelledListener::class], OrderPaid::class => [SendOrderConfirmationNotification::class, SendAdminNewOrderNotification::class], OrderCanceled::class => [SendOrderCanceledNotification::class, SendAdminOrderCanceledNotification::class], OrderShipped::class => [SendOrderShippedNotification::class], OrderDelivered::class => [SendOrderDeliveredNotification::class], PaymentSuccessful::class => [SendPaymentSuccessNotification::class], PaymentFailed::class => [SendPaymentFailedNotification::class, SendAdminPaymentFailedNotification::class], OrderRefunded::class => [SendOrderRefundedNotification::class, SendAdminRefundNotification::class], PartialRefundProcessed::class => [SendPartialRefundNotification::class, SendAdminRefundNotification::class], Abandoned::class => [SendAbandonedCartNotification::class], LowStockAlert::class => [SendLowStockNotification::class], OutOfStockAlert::class => [SendOutOfStockNotification::class]];

    public function boot() {}
}
