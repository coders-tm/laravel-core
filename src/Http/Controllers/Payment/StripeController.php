<?php

namespace Coderstm\Http\Controllers\Payment;

use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Payment;
use Coderstm\Models\Shop\Order;
use Coderstm\Models\PaymentMethod;
use Coderstm\Http\Controllers\Controller;
use Coderstm\Traits\Paymentable;

class StripeController extends Controller
{
    use Paymentable;

    public function token(Request $request)
    {
        $request->validate([
            'key' => 'required|string',
        ]);

        $order = Order::findByKey($request->key)->load('customer');

        $paymentIntent = Cashier::stripe()->paymentIntents->create([
            'automatic_payment_methods' => ['enabled' => true],
            'amount' => $order->grand_total * 100,
            'currency' => $order->currency,
            'metadata' => ['order_id' => $order->id]
        ]);


        return response()->json([
            'order' => array_merge($order->toPublic(), [
                'billing_details' => $this->billingDetails($order),
            ]),
            'successUrl' => route('payment.stripe.success', [
                'key' => $order->key,
                'redirect' => $request->redirect ?? app_url('/billing')
            ]),
            'clientSecret' => $paymentIntent->client_secret
        ], 200);
    }

    private function verifyPayment(Request $request): Order
    {
        $request->validate([
            'key' => 'required|string',
            'payment_intent' => 'required|string',
        ]);

        $payment_intent = $request->payment_intent;
        $order = Order::findByKey($request->key)->load('orderable');
        $orderable = $order->orderable;

        try {
            $payment = new Payment(
                Cashier::stripe()->paymentIntents->retrieve(
                    $payment_intent,
                    ['expand' => ['payment_method']]
                )
            );

            if ($payment->isSucceeded()) {
                $order->markAsPaid(PaymentMethod::stripe()->id, [
                    'id' => $payment->id,
                    'amount' => $payment->amount / 100,
                    'status' => $payment->status,
                ]);

                if ($orderable && method_exists($orderable, 'paymentConfirmation')) {
                    $orderable->paymentConfirmation($order);
                }
            } else {
                // return response()->json(['message' => 'Payment failed'], 500);
            }

            return $order;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    private function billingDetails(Order $order)
    {
        // Check if the order and customer exist
        if (!$order->customer) {
            return [];
        }

        $user = $order->customer;
        $billing_address = $order->billing_address;
        $address = Arr::only((array) $billing_address, ['line1', 'line2', 'city', 'state', 'postal_code']);

        return [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone_number,
            'address' => array_merge($address, [
                'country' => $billing_address['country_code']
            ]),
        ];
    }

    public function webhook(Request $request)
    {
        return response()->json([], 200);
    }
}
