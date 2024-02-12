<?php

namespace Coderstm\Notifications;

use Coderstm\Models\Notification as Template;
use Coderstm\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class SubscriptionExpiredNotification extends Notification
{
    use Queueable;

    public $user;
    public $subscription;
    public $status;
    public $subject;
    public $message;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(User $user, $subscription)
    {
        $this->user = $user;
        $this->subscription = $subscription;
        $this->subject = "Subscription Renewal Required - Your Subscription Has Expired";

        $template = Template::default('user:subscription-expired');
        $shortCodes = [
            '{{USER_NAME}}' => $this->user->name,
            '{{USER_ID}}' => $this->user->id,
            '{{USER_FIRST_NAME}}' => $this->user->first_name,
            '{{USER_LAST_NAME}}' => $this->user->last_name,
            '{{PLAN}}' => optional($this->user->price)->label,
            '{{PLAN_PRICE}}' => format_amount(optional($this->subscription->price)->amount * 100),
            '{{BILLING_CYCLE}}' => optional($this->subscription->price)->interval->value,
            '{{ENDS_AT}}' => $this->subscription->ends_at->format('d M, Y'),
        ];

        $this->subject = replace_short_code($template->subject, $shortCodes);
        $this->message = replace_short_code($template->content, $shortCodes);
        logger($this->message);
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject($this->subject)
            ->markdown('coderstm::emails.notification', [
                'message' => $this->message
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
