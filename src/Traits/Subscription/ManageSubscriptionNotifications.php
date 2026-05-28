<?php

namespace Coderstm\Traits\Subscription;

use Coderstm\Jobs\Subscription\SendRenewNotificationJob;
use Coderstm\Models\Notification;

trait ManageSubscriptionNotifications
{
    public function getShortCodes(): array
    {
        return ['user' => $this->user?->toArray(), 'plan' => ['label' => $this->plan?->label, 'price' => $this->plan?->formatPrice()], 'billing_page' => user_route('/billing'), 'subscription_status' => is_string($this->status) ? $this->status : $this->status->value ?? '', 'billing_cycle' => $this->formatBillingInterval(), 'next_billing_date' => $this->expires_at ? $this->expires_at->format('d M, Y') : '', 'ends_at' => $this->expires_at ? $this->expires_at->format('d M, Y') : '', 'starts_at' => $this->starts_at ? $this->starts_at->format('d M, Y') : '', 'expires_at' => $this->expires_at ? $this->expires_at->format('d M, Y') : '', 'upcoming_invoice' => $this->upcomingInvoice()];
    }

    public function sendRenewNotification(): void
    {
        if (! $this->user) {
            return;
        }
        if ($this->expired()) {
            SendRenewNotificationJob::dispatch($this)->afterResponse();
        }
    }

    public function renderNotification($type, $additionalData = []): ?Notification
    {
        $template = Notification::default($type);
        $data = array_merge($this->getShortCodes(), $additionalData);
        $rendered = $template->render($data);

        return $template->fill(['subject' => $rendered['subject'], 'content' => $rendered['content']]);
    }

    public function renderPushNotification($type, $additionalData = [])
    {
        $template = $this->renderNotification($type, $additionalData);

        return optional((object) ['subject' => $template->subject, 'content' => html_text($template->content), 'whatsappContent' => html_text("{$template->subject}\n{$template->content}"), 'data' => ['route' => user_route('/billing')]]);
    }
}
