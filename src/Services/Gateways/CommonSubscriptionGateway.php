<?php

namespace Coderstm\Services\Gateways;

use Coderstm\Contracts\SubscriptionGateway;
use Coderstm\Models\PaymentMethod;

class CommonSubscriptionGateway implements SubscriptionGateway
{
    protected $subscription;

    protected $plan;

    protected $user;

    protected $order;

    protected $upcomingInvoice;

    protected $options;

    protected $gateway = 'manual';

    public function __construct($subscription)
    {
        $this->gateway = $subscription->provider ?? 'manual';
        $this->subscription = $subscription;
        $this->plan = $subscription->plan;
        $this->user = $subscription->user;
        $this->order = $subscription->latestInvoice;
        $this->upcomingInvoice = $subscription->upcomingInvoice();
        $this->options = $subscription->options ?? [];
    }

    public function setup(mixed $options = null): array
    {
        $payment = false;
        $redirectUrl = null;
        if ($this->payable()) {
            $payment = true;
            $redirectUrl = $this->getRedirectUrl();
        }

        return array_filter(['data' => $this->subscription?->toResponse(['usages', 'next_plan', 'plan']), 'redirect_url' => $redirectUrl ?? null, 'message' => trans_choice('messages.subscription.success', $payment ? 1 : 0, ['plan' => $this->plan->label])]);
    }

    protected function payable()
    {
        return $this->order?->has_due && ! in_array($this->gateway, [PaymentMethod::MANUAL, PaymentMethod::GOCARDLESS]);
    }

    protected function getRedirectUrl()
    {
        return user_route('/payment/'.$this->order->key, ['redirect' => user_route('/billing')]);
    }

    public function getProviderId()
    {
        return data_get($this->options, $this->gateway.'_provider_id');
    }

    public function completeSetup($setupId) {}

    public function create(array $options = []) {}

    public function update(array $params = []) {}

    public function updatePlan(bool $hasIntervalChanged, bool $hasPriceChanged) {}

    public function cancel(array $metadata = []) {}

    public function charge($description, array $metadata = []) {}
}
