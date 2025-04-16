<?php
/*   __________________________________________________
    |  Obfuscated by YAK Pro - Php Obfuscator  2.0.16  |
    |              on 2025-04-16 10:35:11              |
    |    GitHub: https://github.com/pk-fr/yakpro-po    |
    |__________________________________________________|
*/
/*

*/
 namespace Coderstm\Providers; use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider; class CoderstmEventServiceProvider extends ServiceProvider { protected $listen = [\Coderstm\Events\EnquiryCreated::class => [\Coderstm\Listeners\SendEnquiryNotification::class, \Coderstm\Listeners\SendEnquiryConfirmation::class], \Coderstm\Events\EnquiryReplyCreated::class => [\Coderstm\Listeners\SendEnquiryReplyNotification::class], \Coderstm\Events\TaskCreated::class => [\Coderstm\Listeners\SendTaskUsersNotification::class], \Coderstm\Events\UserSubscribed::class => [\Coderstm\Listeners\SendSignupNotification::class], \Illuminate\Notifications\Events\NotificationFailed::class => [\Coderstm\Listeners\DeleteExpiredNotificationTokens::class]]; public function boot() { } }