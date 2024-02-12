<?php

namespace Coderstm\Notifications;

use Coderstm\Models\Notification as Template;
use Coderstm\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class UserSignupNotification extends Notification
{
    use Queueable;

    public $user;
    public $subject;
    public $message;
    public $subscription;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(User $user)
    {
        $this->user = $user;
        $this->subject = "Welcome to " . config('app.name') . " - Your Subscription Details";
        $this->subscription = $user->subscription();

        $template = Template::default('user:signup');
        $shortCodes = [
            '{{USER_NAME}}' => $this->user->name,
            '{{USER_ID}}' => $this->user->id,
            '{{USER_FIRST_NAME}}' => $this->user->first_name,
            '{{USER_LAST_NAME}}' => $this->user->last_name,
            '{{PLAN}}' => optional($this->user->price)->label,
            '{{PLAN_PRICE}}' => format_amount(optional($this->subscription->price)->amount * 100),
            '{{BILLING_CYCLE}}' => optional($this->subscription->price)->interval->value,
        ];

        $this->subject = replace_short_code($template->subject, $shortCodes);
        $this->message = replace_short_code($template->content, $shortCodes);
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
