<?php

namespace Coderstm\Traits\Subscription;

use Coderstm\Models\Notification;
use Coderstm\Notifications\SubscriptionRenewedNotification;

use function Illuminate\Events\queueable;

trait HandlesSubscriptionNotifications
{
    /**
     * Send renewal notification to the user.
     *
     * @return void
     */
    public function sendRenewNotification()
    {
        if ($this->pastDue()) {
            queueable(function () {
                $this->user->notify(new SubscriptionRenewedNotification($this));
            });
        }
    }

    /**
     * Render a notification from template for this subscription.
     *
     * @param string $type The notification type
     * @param array $shortCodes Additional shortcodes to replace in the template
     * @return \Coderstm\Models\Notification|null
     */
    public function renderNotification($type, $shortCodes = []): ?Notification
    {
        $template = Notification::default($type);
        $userShortCodes = $this->user->getShortCodes() ?? [];
        $upcomingInvoice = $this->upcomingInvoice();

        $shortCodes = array_merge($shortCodes, $userShortCodes, [
            '{{PLAN}}' => optional($this->plan)->label,
            '{{PLAN_PRICE}}' => $this->plan->formatPrice(),
            '{{BILLING_CYCLE}}' => optional($this->plan)->interval->value,
            '{{NEXT_BILLING_DATE}}' => $upcomingInvoice ? $upcomingInvoice->due_date->format('d M, Y') : '',
            '{{ENDS_AT}}' => $this->ends_at ? $this->ends_at->format('d M, Y') : '',
            '{{STARTS_AT}}' => $this->starts_at ? $this->starts_at->format('d M, Y') : '',
            '{{EXPIRES_AT}}' => $this->expires_at ? $this->expires_at->format('d M, Y') : '',
        ]);

        return $template->fill([
            'subject' => replace_short_code($template->subject, $shortCodes),
            'content' => replace_short_code($template->content, $shortCodes),
        ]);
    }

    /**
     * Render a push notification for this subscription.
     *
     * @param string $type The notification type
     * @param array $shortCodes Additional shortcodes to replace in the template
     * @return object|null
     */
    public function renderPushNotification($type, $shortCodes = [])
    {
        $template = $this->renderNotification($type, $shortCodes);

        return optional((object) [
            'subject' => $template->subject,
            'content' => html_text($template->content),
            'whatsappContent' => html_text("{$template->subject}\n{$template->content}"),
            'data' => [
                'route' => user_route("/billing"),
            ]
        ]);
    }
}
