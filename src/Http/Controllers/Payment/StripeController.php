<?php

namespace Coderstm\Http\Controllers\Payment;

use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Payment;
use Coderstm\Models\Shop\Order;
use Coderstm\Models\PaymentMethod;
use Coderstm\Http\Controllers\Controller;

class StripeController extends Controller
{
    public function token(Request $request)
    {
        $request->validate([
            'key' => 'required|string',
        ]);

        $order = Order::findByKey($request->key)->load('customer');

        $paymentIntent = Cashier::stripe()->paymentIntents->create([
            'automatic_payment_methods' => ['enabled' => true],
            'amount' => $order->grand_total * 100,
            'currency' => $order->currency ?? config('cashier.currency'),
        ]);


        return response()->json([
            'order' => array_merge($order->toPublic(), [
                'billing_details' => $this->billingDetails($order),
            ]),
            'client_secret' => $paymentIntent->client_secret
        ], 200);
    }

    public function success(Request $request)
    {
        $this->processPayment($request);

        return redirect(app_url('/billing'));
    }

    public function process(Request $request)
    {
        return response()->json($this->processPayment($request), 200);
    }

    private function processPayment(Request $request): Order
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

                if ($orderable) {
                    $orderable->paymentConfirmation($order);
                }
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
                'country' => 'US'
            ]),
        ];
    }
}
