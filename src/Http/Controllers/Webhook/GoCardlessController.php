<?php

namespace Coderstm\Http\Controllers\Webhook;

use Coderstm\Coderstm;
use Coderstm\Events\GoCardless\Mandate;
use Coderstm\Events\GoCardless\Payment;
use Coderstm\Events\GoCardless\Subscription as SubscriptionEvent;
use Coderstm\Events\GoCardless\WebhookHandled;
use Coderstm\Events\GoCardless\WebhookReceived;
use Coderstm\Models\Payment as ModelsPayment;
use Coderstm\Models\Shop\Order;
use Coderstm\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class GoCardlessController extends Controller
{
    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $webhookSignature = $request->header('Webhook-Signature');
        if (! $this->verifyWebhookSignature($payload, $webhookSignature)) {
            return response('Webhook signature verification failed', 401);
        }
        $data = json_decode($payload, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return response('Invalid payload', 400);
        }
        try {
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
        } catch (\Throwable $e) {
            return response('Webhook processing failed: '.$e->getMessage(), 500);
        }
    }

    protected function verifyWebhookSignature($payload, $signature)
    {
        if (empty($signature)) {
            return false;
        }
        $webhookSecret = config('gocardless.webhook_secret');
        if (empty($webhookSecret)) {
            return false;
        }
        $expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);

        return hash_equals($expectedSignature, $signature);
    }

    protected function processEvent($event)
    {
        $resourceType = $event['resource_type'] ?? '';
        $action = $event['action'] ?? '';
        if (empty($resourceType) || empty($action)) {
            return;
        }
        $methodName = 'handle'.Str::studly($resourceType).Str::studly($action);
        if (method_exists($this, $methodName)) {
            $this->{$methodName}($event);
        }
    }

    protected function successMethod($parameters = [])
    {
        return new Response('Webhook Handled', 200);
    }

    protected function missingMethod($parameters = [])
    {
        return new Response;
    }

    protected function findOrder($orderId)
    {
        if (! $orderId) {
            return null;
        }

        return Order::find($orderId)?->load('orderable') ?? null;
    }

    protected function findSubscriptionByMandateId($mandateId)
    {
        if (! $mandateId) {
            return null;
        }

        return Subscription::where('options->gocardless_provider_id', $mandateId)->first();
    }

    protected function isPaymentAlreadyProcessed(string $paymentId): bool
    {
        return ModelsPayment::where('payment_method_id', config('gocardless.id'))->where('transaction_id', $paymentId)->exists();
    }

    protected function handlePaymentsConfirmed($event)
    {
        Payment\PaymentConfirmed::dispatch($event);
        $paymentId = $event['links']['payment'] ?? null;
        $orderId = $event['resource_metadata']['order_id'] ?? null;
        if (! $paymentId) {
            return;
        }
        try {
            $payment = Coderstm::gocardless()->payments()->get($paymentId);
            $mandateId = $payment->links->mandate ?? null;
            if ($order = $this->findOrder($orderId)) {
                $order->markAsPaid(config('gocardless.id'), ['id' => $payment->id, 'amount' => $payment->amount / 100, 'status' => $payment->status]);
            } elseif ($mandateId && ($subscription = $this->findSubscriptionByMandateId($mandateId))) {
                if ($this->isPaymentAlreadyProcessed($payment->id)) {
                    return;
                }
                $paymentData = ['id' => $payment->id, 'amount' => $payment->amount / 100, 'status' => $payment->status, 'note' => 'Payment confirmed via GoCardless'];
                if ($subscription->hasIncompletePayment()) {
                    $subscription->pay(config('gocardless.id'), $paymentData);
                } else {
                    $subscription->renew();
                    $subscription = $subscription->refresh(['latestInvoice']);
                    $subscription->pay(config('gocardless.id'), $paymentData);
                }
            }
        } catch (\Throwable $e) {
            Log::error('Error processing payment confirmed event', ['error' => $e->getMessage(), 'payment_id' => $paymentId, 'order_id' => $orderId, 'mandate_id' => $mandateId ?? null]);
        }
    }

    protected function handlePaymentsFailed($event)
    {
        Payment\PaymentFailed::dispatch($event);
        $paymentId = $event['links']['payment'] ?? null;
        $orderId = $event['resource_metadata']['order_id'] ?? null;
        if (! $paymentId) {
            return;
        }
        try {
            $payment = Coderstm::gocardless()->payments()->get($paymentId);
            $mandateId = $payment->links->mandate ?? null;
            $paymentData = ['id' => $payment->id, 'amount' => $payment->amount / 100, 'status' => $payment->status];
            if ($order = $this->findOrder($orderId)) {
                $order->markAsPaymentFailed(config('gocardless.id'), $paymentData);
            } elseif ($mandateId && ($subscription = $this->findSubscriptionByMandateId($mandateId))) {
                if ($this->isPaymentAlreadyProcessed($payment->id)) {
                    return;
                }
                if ($subscription->hasIncompletePayment()) {
                    return;
                }
                $subscription->renew();
                $subscription = $subscription->refresh(['latestInvoice']);
                if ($order = $subscription->latestInvoice) {
                    $order->markAsPaymentFailed(config('gocardless.id'), ['id' => $payment->id, 'amount' => $payment->amount / 100, 'status' => $payment->status, 'note' => 'Payment failed via GoCardless']);
                    $subscription->paymentFailed($order);
                }
            }
        } catch (\Throwable $e) {
            Log::error('Error processing payment failed event', ['error' => $e->getMessage(), 'payment_id' => $paymentId, 'order_id' => $orderId, 'mandate_id' => $mandateId ?? null]);
        }
    }

    protected function handleSubscriptionsCreated($event)
    {
        SubscriptionEvent\SubscriptionCreated::dispatch($event);
    }

    protected function handleSubscriptionsCancelled($event)
    {
        SubscriptionEvent\SubscriptionCancelled::dispatch($event);
    }

    protected function handleMandatesActive($event)
    {
        Mandate\MandateActive::dispatch($event);
    }

    protected function handleMandatesFailed($event)
    {
        Mandate\MandateFailed::dispatch($event);
        $mandateId = $event['links']['mandate'] ?? null;
        if (! $mandateId) {
            return;
        }
        try {
            $subscription = $this->findSubscriptionByMandateId($mandateId);
            if (! $subscription) {
                $mandate = Coderstm::gocardless()->mandates()->get($mandateId);
                $subscriptionId = $mandate->metadata->subscription_id ?? null;
                if ($subscriptionId) {
                    $subscription = Subscription::find($subscriptionId);
                }
            }
            if ($subscription) {
                $subscription->paymentFailed();
            }
        } catch (\Throwable $e) {
            Log::error('Error processing mandate failed event', ['error' => $e->getMessage(), 'mandate_id' => $mandateId]);
        }
    }

    protected function handleMandatesCancelled($event)
    {
        Mandate\MandateCancelled::dispatch($event);
    }
}
