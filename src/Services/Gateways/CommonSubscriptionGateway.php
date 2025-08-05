<?php

namespace Coderstm\Services\Gateways;

use Coderstm\Coderstm;
use Coderstm\Models\PaymentMethod;
use Coderstm\Contracts\SubscriptionGateway;

class CommonSubscriptionGateway implements SubscriptionGateway
{
    /**
     * @var \Coderstm\Models\Subscription
     */
    protected $subscription;

    /**
     * @var \Coderstm\Models\Subscription\Plan
     */
    protected $plan;

    /**
     * @var \Coderstm\Models\User
     */
    protected $user;

    /**
     * @var \Coderstm\Models\Shop\Order
     */
    protected $order;

    /**
     * @var \Coderstm\Repositories\InvoiceRepository
     */
    protected $upcomingInvoice;

    /**
     * @var array
     */
    protected $options;

    /**
     * @var string
     */
    protected $gateway = 'manual';

    /**
     * Create a new service instance.
     *
     * @param \Coderstm\Models\Subscription $subscription
     * @return void
     */
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

    /**
     * Set up the subscription.
     *
     * @param mixed $options
     * @return array
     */
    public function setup(mixed $options = null): array
    {
        $payment = false;
        $redirectUrl = null;

        if ($this->payable()) {
            $payment = true;
            $redirectUrl = $this->getRedirectUrl();
        }

        return array_filter([
            'subscription' => $this->subscription,
            'redirect_url' => $redirectUrl ?? null,
            'message' => trans_choice('messages.subscription.success', $payment ? 1 : 0, [
                'plan' => $this->plan->label
            ])
        ]);
    }

    protected function payable()
    {
        return $this->order?->has_due && !in_array($this->gateway, [
            PaymentMethod::MANUAL,
            PaymentMethod::GOCARDLESS
        ]);
    }

    protected function getRedirectUrl()
    {
        return user_route('/payment/' . $this->gateway, [
            'key' => $this->order->key,
            'redirect' => user_route('/billing')
        ]);
    }

    public function getProviderId()
    {
        return data_get($this->options, $this->gateway . '_provider_id');
    }

    public function completeSetup($setupId)
    {
        // do nothing
    }

    public function create(array $options = [])
    {
        // do nothing
    }

    public function update(array $params = [])
    {
        // do nothing
    }

    public function updatePlan(bool $hasIntervalChanged, bool $hasPriceChanged)
    {
        // do nothing
    }

    public function cancel(array $metadata = [])
    {
        // do nothing
    }

    public function charge($description, array $metadata = [])
    {
        // do nothing
    }
}
