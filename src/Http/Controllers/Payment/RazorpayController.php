<?php

namespace Coderstm\Http\Controllers\Payment;

use Coderstm\Coderstm;
use Coderstm\Http\Controllers\Controller;
use Coderstm\Models\Shop\Order;
use Coderstm\Traits\Paymentable;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RazorpayController extends Controller
{
    use Paymentable;

    public function token(Request $request)
    {
        $request->validate([
            'key' => 'required|string',
        ]);

        $order = Coderstm::$orderModel::findByKey($request->key)->load('customer');

        $paymentIntent = Coderstm::razorpay()->order->create([
            'receipt' => $order->formated_id,
            'amount' => (int) $order->grand_total * 100,
            'currency' => Str::upper($order->currency),
        ]);

        return response()->json([
            'order' => array_merge($order->toPublic(), [
                'billing_details' => $this->billingDetails($order),
            ]),
            'orderID' => $paymentIntent['id'],
            'successUrl' => route('payment.razorpay.success', [
                'key' => $order->key,
                'redirect' => $request->redirect ?? app_url('/billing'),
            ]),
        ], 200);
    }

    /**
     * Verify payment.
     *
     * @return mixed
     */
    private function verifyPayment(Request $request)
    {
        $request->validate([
            'key' => 'required|string',
            'razorpay_signature' => 'required|string',
            'razorpay_payment_id' => 'required|string',
            'razorpay_order_id' => 'required|string',
        ]);

        $order = Coderstm::$orderModel::findByKey($request->key)->load('orderable');
        $orderable = $order->orderable;

        try {
            // Verify the signature
            Coderstm::razorpay()->utility->verifyPaymentSignature($request->only([
                'razorpay_signature',
                'razorpay_payment_id',
                'razorpay_order_id',
            ]));

            // Fetch the payment details
            $payment = Coderstm::razorpay()->payment->fetch($request->razorpay_payment_id);

            // Payment is successful, proceed with your business logic
            $order->markAsPaid(config('razorpay.id'), [
                'id' => $request->razorpay_payment_id,
                'amount' => $payment->amount / 100,
                'status' => $payment->status,
            ]);

            return $order;
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    /**
     * Get billing details.
     *
     * @param  mixed  $order
     * @return array
     */
    private function billingDetails($order)
    {
        // Check if the order and customer exist
        if (! $order->customer) {
            return [];
        }

        $user = $order->customer;

        return [
            'name' => $user->name,
            'email' => $user->email,
            'contact' => $user->phone_number,
        ];
    }

    public function webhook(Request $request)
    {
        return response()->json([], 200);
    }
}
