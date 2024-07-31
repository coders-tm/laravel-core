<?php

namespace Coderstm\Http\Controllers\Payment;

use Illuminate\Http\Request;
use Coderstm\Models\Shop\Order;
use Coderstm\Models\PaymentMethod;
use Coderstm\Http\Controllers\Controller;
use Srmklive\PayPal\Services\PayPal as PayPalClient;

class PaypalController extends Controller
{
    protected PayPalClient $provider;

    function __construct()
    {
        $this->provider = new PayPalClient;
        $this->provider->setApiCredentials(config('paypal'));
        $this->provider->getAccessToken();
    }

    public function token(Request $request)
    {
        $request->validate([
            'key' => 'required|string',
        ]);

        $order = Order::findByKey($request->key)->load('customer');

        if (true) {
            return response()->json([
                'order' => $order->toPublic()
            ], 200);
        } else {
            return response()->json([
                'error' => $response['message'] ?? 'Something went wrong.'
            ], 500);
        }
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
            'orderID' => 'required|string',
            'paymentID' => 'required|string',
        ]);

        $orderID = $request->orderID;
        $paymentID = $request->paymentID;
        $order = Order::findByKey($request->key)->load('orderable');
        $orderable = $order->orderable;

        try {
            $payment = $this->provider->capturePaymentOrder($orderID);

            if (isset($payment['status']) && $payment['status'] == 'COMPLETED') {
                $order->markAsPaid(PaymentMethod::paypal()->id, [
                    'id' => $paymentID,
                    'amount' => $order->due_amount,
                    'status' => $payment['status'],
                ]);

                if ($orderable) {
                    $orderable->paymentConfirmation($order);
                }
            } else {
                // return response()->json(['message' => $response['message'] ?? 'Payment failed'], 500);
            }

            return $order;
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
