<?php

namespace Coderstm\Http\Controllers\Webhook;

use Coderstm\Coderstm;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Coderstm\Models\Shop\Order;
use Coderstm\Models\Subscription;
use Coderstm\Models\PaymentMethod;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Coderstm\Events\GoCardless\Mandate;
use Coderstm\Events\GoCardless\Payment;
use Coderstm\Contracts\SubscriptionStatus;
use Coderstm\Events\GoCardless\WebhookHandled;
use Symfony\Component\HttpFoundation\Response;
use Coderstm\Events\GoCardless\WebhookReceived;
use Coderstm\Events\GoCardless\Subscription as SubscriptionEvent;

class GoCardlessController extends Controller
{
    /**
     * Handle incoming GoCardless webhook
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $webhookSignature = $request->header('Webhook-Signature');

        // Verify webhook signature
        if (!$this->verifyWebhookSignature($payload, $webhookSignature)) {
            return response('Webhook signature verification failed', 401);
        }

        $data = json_decode($payload, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return response('Invalid payload', 400);
        }

        try {
            // Process events
            $events = $data['events'] ?? [];

            if (empty($events)) {
                return $this->missingMethod();
            }

            WebhookReceived::dispatch($data);

            foreach ($events as $event) {
                $this->processEvent($event);
            }

            WebhookHandled::dispatch($data);

            return $this->successMethod();
        } catch (\Exception $e) {
            return response('Webhook processing failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Verify webhook signature
     *
     * @param string $payload
     * @param string $signature
     * @return bool
     */
    protected function verifyWebhookSignature($payload, $signature)
    {
        if (empty($signature)) {
            return false;
        }

        // Get webhook secret from config
        $webhookSecret = config('gocardless.webhook_secret');

        if (empty($webhookSecret)) {
            return false;
        }

        // GoCardless sends the raw signature as a hex string
        // Calculate expected signature
        $expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Process GoCardless event using dynamic method dispatch
     *
     * @param array $event
     * @return void
     */
    protected function processEvent($event)
    {
        $resourceType = $event['resource_type'] ?? '';
        $action = $event['action'] ?? '';

        if (empty($resourceType) || empty($action)) {
            return;
        }

        // Create method name like handlePaymentsConfirmed
        $methodName = 'handle' . Str::studly($resourceType) . Str::studly($action);

        if (method_exists($this, $methodName)) {
            $this->{$methodName}($event);
        }
    }

    /**
     * Handle successful calls on the controller.
     *
     * @param  array  $parameters
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function successMethod($parameters = [])
    {
        return new Response('Webhook Handled', 200);
    }

    /**
     * Handle calls to missing methods on the controller.
     *
     * @param  array  $parameters
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function missingMethod($parameters = [])
    {
        return new Response;
    }

    /**
     * Handle payment confirmed events
     *
     * @param array $event
     * @return void
     */
    protected function handlePaymentsConfirmed($event)
    {
        Payment\PaymentConfirmed::dispatch($event);

        $paymentId = $event['links']['payment'] ?? null;

        if (!$paymentId) {
            return;
        }

        try {
            // Get the payment details from GoCardless
            $payment = Coderstm::gocardless()->payments()->get($paymentId);
            $orderId = $payment->metadata->order_id ?? null;

            if ($order = Order::find($orderId)?->load('orderable')) {
                $orderable = $order->orderable;

                // Update order payment status
                $order->markAsPaid(config('gocardless.id'), [
                    'id' => $payment->id,
                    'amount' => $payment->amount / 100,
                    'status' => $payment->status,
                ]);

                if ($orderable && method_exists($orderable, 'paymentConfirmation')) {
                    $orderable->paymentConfirmation($order);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error processing payment confirmed event: ' . $e->getMessage());
        }
    }

    /**
     * Handle payment failed events
     *
     * @param array $event
     * @return void
     */
    protected function handlePaymentsFailed($event)
    {
        Payment\PaymentFailed::dispatch($event);

        $paymentId = $event['links']['payment'] ?? null;

        if (!$paymentId) {
            return;
        }

        try {
            // Get the payment details from GoCardless
            $payment = Coderstm::gocardless()->payments()->get($paymentId);
            $orderId = $payment->metadata->order_id ?? null;

            if ($order = Order::find($orderId)?->load('orderable')) {
                $orderable = $order->orderable;

                $order->markAsPaymentFailed(config('gocardless.id'), [
                    'id' => $payment->id,
                    'amount' => $payment->amount / 100,
                    'status' => $payment->status,
                ]);

                if ($orderable && method_exists($orderable, 'paymentFailed')) {
                    $orderable->paymentFailed($order);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error processing payment failed event: ' . $e->getMessage());
        }
    }

    /**
     * Handle subscription created events
     *
     * @param array $event
     * @return void
     */
    protected function handleSubscriptionsCreated($event)
    {
        SubscriptionEvent\SubscriptionCreated::dispatch($event);
    }

    /**
     * Handle subscription cancelled events
     *
     * @param array $event
     * @return void
     */
    protected function handleSubscriptionsCancelled($event)
    {
        SubscriptionEvent\SubscriptionCancelled::dispatch($event);
    }

    /**
     * Handle mandate active events
     *
     * @param array $event
     * @return void
     */
    protected function handleMandatesActive($event)
    {
        Mandate\MandateActive::dispatch($event);
    }

    /**
     * Handle mandate failed events
     *
     * @param array $event
     * @return void
     */
    protected function handleMandatesFailed($event)
    {
        Mandate\MandateFailed::dispatch($event);

        $mandateId = $event['links']['mandate'] ?? null;

        if (!$mandateId) {
            return;
        }

        try {
            // Get the mandate details from GoCardless to find linked subscription
            $mandate = Coderstm::gocardless()->mandates()->get($mandateId);

            $subscriptionId = $mandate->metadata->subscription_id ?? null;
            if (!$subscriptionId) {
                return;
            }

            if ($subscription = Subscription::find($subscriptionId)) {
                // Cancel the subscription
                $subscription->status = SubscriptionStatus::INCOMPLETE;
                $subscription->save();
            }
        } catch (\Exception $e) {
            Log::error('Error processing mandate failed event: ' . $e->getMessage());
        }
    }

    /**
     * Handle mandate cancelled events
     *
     * @param array $event
     * @return void
     */
    protected function handleMandatesCancelled($event)
    {
        Mandate\MandateCancelled::dispatch($event);
    }
}
