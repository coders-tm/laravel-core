<?php

namespace Coderstm\Payment\Processors;

use Coderstm\Coderstm;
use Illuminate\Http\Request;
use Coderstm\Models\Shop\Checkout;
use Coderstm\Models\PaymentMethod;
use Illuminate\Support\Facades\DB;
use Coderstm\Payment\Mappers\StripePayment;
use Coderstm\Contracts\PaymentProcessorInterface;

class StripeProcessor extends AbstractPaymentProcessor implements PaymentProcessorInterface
{
    protected PaymentMethod $paymentMethod;

    public function __construct()
    {
        $this->paymentMethod = PaymentMethod::where('provider', 'stripe')->where('active', true)->firstOrFail();
    }

    public function getProvider(): string
    {
        return 'stripe';
    }

    public function setupPaymentIntent(Request $request, Checkout $checkout): array
    {
        $stripe = Coderstm::stripe();

        $intent = $stripe->paymentIntents->create([
            'amount' => round($checkout->grand_total * 100), // Convert to cents
            'currency' => strtolower($checkout->currency ?? 'usd'),
            'metadata' => [
                'checkout_token' => $checkout->token,
                'customer_email' => $checkout->email,
            ],
            'description' => "Order payment for {$checkout->email}",
            'receipt_email' => $checkout->email,
            'automatic_payment_methods' => [
                'enabled' => true,
            ],
        ]);

        return [
            'client_secret' => $intent->client_secret,
            'payment_intent_id' => $intent->id,
        ];
    }

    public function confirmPayment(Request $request, Checkout $checkout): array
    {
        $request->validate([
            'payment_intent_id' => 'required|string',
        ]);

        DB::beginTransaction();

        try {
            $stripe = Coderstm::stripe();
            $intent = $stripe->paymentIntents->retrieve($request->payment_intent_id);

            // Handle different payment intent statuses
            if (!in_array($intent->status, ['succeeded', 'requires_capture'])) {
                throw new \Exception("Payment not completed. Status: {$intent->status}" .
                    ($intent->status === 'requires_action' ? ' (requires additional action)' : ''));
            }

            $order = $this->createOrderFromCheckout($checkout);

            $paymentData = StripePayment::fromIntent(
                $intent->toArray(),
                $this->paymentMethod->id,
                $checkout->grand_total,
                $checkout->currency ?? config('app.currency')
            );

            $order->markAsPaid($paymentData);

            DB::commit();

            return [
                'success' => true,
                'order_id' => $order->key,
                'transaction_id' => $intent->id,
                'status' => 'success',
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
