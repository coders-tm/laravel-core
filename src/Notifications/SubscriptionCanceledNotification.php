<?php

namespace Coderstm\Notifications;

use Coderstm\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class SubscriptionCanceledNotification extends Notification
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
        $this->subject = "Subscription Cancellation Notification - Your Subscription Has Canceled";
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
            ->markdown('coderstm::emails.user.subscription-canceled', [
                'name' => $this->user->first_name,
                'plan' => optional($this->user->price)->label,
                'ends_at' => $this->subscription->ends_at->format('d M, Y'),
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
