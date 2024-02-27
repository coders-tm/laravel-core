<?php

namespace Coderstm\Listeners\Cashier;

use Carbon\Carbon;
use Coderstm\Models\Cashier\Invoice;
use Illuminate\Support\Str;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Events\WebhookReceived;

class CashierEventListener
{
    /**
     * Handle received Cashier webhooks.
     *
     * @param  \Laravel\Cashier\Events\WebhookReceived  $event
     * @return void
     */
    public function handle(WebhookReceived $event)
    {
        $payload = $event->payload;
        $method = 'handle' . Str::studly(str_replace('.', '_', $payload['type']));

        if (method_exists($this, $method)) {
            $this->{$method}($payload);
        }
    }

    /**
     * Handle plan updated.
     *
     * @param  array  $payload
     * @return void
     */
    protected function handlePlanUpdated(array $payload)
    {
        // $plan = Plan::findPlanable($payload['data']['object']['id']);
        // $plan->label = $payload['data']['object']['nickname'] / 100;
        // $plan->fee = $payload['data']['object']['amount'] / 100;
        // $plan->type = $payload['data']['object']['interval'] == 'year' ? 'yearly' : 'monthly';
        // $plan->save();
    }

    /**
     * Handle invoice payment succeeded.
     *
     * @param  array  $payload
     * @return void
     */
    protected function handleInvoicePaymentSucceeded(array $payload)
    {
        if ($subscription = $this->getSubscriptionByStripeId($payload['data']['object']['subscription'])) {
            $data = $payload['data']['object'];

            try {
                $dueDate = $data['due_date'] ? Carbon::createFromTimestampUTC($data['due_date']) : null;

                $invoice = Invoice::updateOrCreate([
                    'stripe_id' => $data['id']
                ], [
                    'subscription_id' => $subscription->id,
                    'number' => $data['number'],
                    'currency' => $data['currency'],
                    'total' => $data['total'] ?? 0,
                    'stripe_status' => $data['status'],
                    'due_date' => $dueDate,
                ]);

                if ($lines = $data['lines']['data']) {
                    $invoice->lines()->whereNotIn('stripe_id', collect($lines)->pluck('id')->toArray())->delete();
                    foreach ($lines as $item) {
                        $invoice->lines()->updateOrCreate([
                            'stripe_id' => $item['id']
                        ], [
                            'description' => $item['description'] ?? null,
                            'stripe_price' => isset($item['price']['id']) ? $item['price']['id'] : null,
                            'stripe_plan' => isset($item['plan']['id']) ? $item['plan']['id'] : null,
                            'quantity' => $item['quantity'] ?? 1,
                            'amount' => $item['amount'] ?? 0,
                            'currency' => $item['currency'],
                        ]);
                    }
                }

                if ($invoice->wasRecentlyCreated) {
                    $subscription->usages()->delete();
                } else {
                    $subscription->syncUsagesResetAt();
                }
            } catch (\Exception $e) {
                report($e);
            }
        }
    }

    /**
     * Get the customer instance by Stripe ID.
     *
     * @param  string|null  $stripeId
     * @return \Laravel\Cashier\Billable|null
     */
    protected function getUserByStripeId($stripeId)
    {
        return Cashier::findBillable($stripeId);
    }

    /**
     * Get the subscription instance by Stripe ID.
     *
     * @param  string|null  $stripeId
     * @return \Laravel\Cashier\Cashier::$subscriptionModel|null
     */
    protected function getSubscriptionByStripeId($stripeId)
    {
        return $stripeId ? (new Cashier::$subscriptionModel)->where('stripe_id', $stripeId)->first() : null;
    }
}
