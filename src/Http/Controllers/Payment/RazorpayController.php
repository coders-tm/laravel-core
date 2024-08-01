<?php

namespace Coderstm\Http\Controllers\Payment;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Coderstm\Models\Shop\Order;
use Coderstm\Traits\Paymentable;
use Razorpay\Api\Api as Razorpay;
use Coderstm\Models\PaymentMethod;
use Coderstm\Http\Controllers\Controller;

class RazorpayController extends Controller
{
    use Paymentable;

    protected Razorpay $provider;

    function __construct()
    {
        $this->provider = new Razorpay(config('razorpay.key_id'), config('razorpay.key_secret'));
    }

    public function token(Request $request)
    {
        $request->validate([
            'key' => 'required|string',
        ]);

        $order = Order::findByKey($request->key)->load('customer');

        $paymentIntent = $this->provider->order->create([
            'receipt' => $order->formated_id,
            'amount' => $order->grand_total * 100,
            'currency' => Str::upper($order->currency),
        ]);

        return response()->json([
            'order' => array_merge($order->toPublic(), [
                'billing_details' => $this->billingDetails($order),
            ]),
            'orderID' => $paymentIntent['id'],
            'successUrl' => route('payment.razorpay.success', [
                'key' => $order->key,
                'redirect' => $request->redirect ?? app_url('/billing')
            ]),
        ], 200);
    }

    private function verifyPayment(Request $request): Order
    {
        $request->validate([
            'key' => 'required|string',
            'razorpay_signature' => 'required|string',
            'razorpay_payment_id' => 'required|string',
            'razorpay_order_id' => 'required|string',
        ]);

        $order = Order::findByKey($request->key)->load('orderable');
        $orderable = $order->orderable;

        try {
            // Verify the signature
            $this->provider->utility->verifyPaymentSignature($request->only([
                'razorpay_signature',
                'razorpay_payment_id',
                'razorpay_order_id',
            ]));

            // Fetch the payment details
            $payment = $this->provider->payment->fetch($request->razorpay_payment_id);

            // Payment is successful, proceed with your business logic
            $order->markAsPaid(PaymentMethod::razorpay()->id, [
                'id' => $request->razorpay_payment_id,
                'amount' => $payment->amount / 100,
                'status' => $payment->status,
            ]);

            if ($orderable && method_exists($orderable, 'paymentConfirmation')) {
                $orderable->paymentConfirmation($order);
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

        return [
            'name' => $user->name,
            'email' => $user->email,
            'contact' => $user->phone_number,
        ];
    }
}
