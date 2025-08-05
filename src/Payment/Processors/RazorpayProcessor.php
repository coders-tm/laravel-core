<?php

namespace Coderstm\Payment\Processors;

use Coderstm\Coderstm;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Coderstm\Models\Shop\Checkout;
use Coderstm\Models\PaymentMethod;
use Illuminate\Support\Facades\DB;
use Coderstm\Payment\Mappers\RazorpayPayment;
use Coderstm\Contracts\PaymentProcessorInterface;

class RazorpayProcessor extends AbstractPaymentProcessor implements PaymentProcessorInterface
{
    protected PaymentMethod $paymentMethod;

    public function __construct()
    {
        $this->paymentMethod = PaymentMethod::where('provider', 'razorpay')->where('active', true)->firstOrFail();
    }

    public function getProvider(): string
    {
        return 'razorpay';
    }

    public function setupPaymentIntent(Request $request, Checkout $checkout): array
    {
        $api = Coderstm::razorpay();

        $order = $api->order->create([
            'amount' => round($request->amount * 100), // Convert to paise
            'currency' => Str::upper($request->currency ?? config('app.currency', 'USD')),
            'receipt' => $checkout->token,
            'notes' => [
                'checkout_token' => $checkout->token,
                'customer_email' => $checkout->email,
            ],
        ]);

        return [
            'order_id' => $order['id'],
            'amount' => $order['amount'],
            'currency' => $order['currency'],
        ];
    }

    public function confirmPayment(Request $request, Checkout $checkout): array
    {
        $request->validate([
            'payment_id' => 'required|string',
            'order_id' => 'required|string',
            'signature' => 'required|string',
        ]);

        DB::beginTransaction();

        try {
            $api = Coderstm::razorpay();

            // Verify payment signature
            $attributes = [
                'razorpay_order_id' => $request->order_id,
                'razorpay_payment_id' => $request->payment_id,
                'razorpay_signature' => $request->signature,
            ];

            $api->utility->verifyPaymentSignature($attributes);

            $payment_details = $api->payment->fetch($request->payment_id);

            if ($payment_details['status'] !== 'captured') {
                throw new \Exception('Payment not captured');
            }

            $order = $this->createOrderFromCheckout($checkout);

            $paymentData = RazorpayPayment::fromPayment(
                $payment_details->toArray(),
                $this->paymentMethod->id,
                $checkout->grand_total,
                $checkout->currency ?? 'INR'
            );

            $order->markAsPaid($paymentData);

            DB::commit();

            return [
                'success' => true,
                'order_id' => $order->key,
                'transaction_id' => $request->payment_id,
                'status' => 'success',
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
