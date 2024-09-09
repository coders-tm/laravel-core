<?php

namespace Coderstm\Http\Controllers\Payment;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Coderstm\Models\Shop\Order;
use Coderstm\Traits\Paymentable;
use Coderstm\Models\PaymentMethod;
use Coderstm\Http\Controllers\Controller;
use Srmklive\PayPal\Services\PayPal as PayPalClient;

class PaypalController extends Controller
{
    use Paymentable;

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

        $response = $this->provider->createOrder([
            "intent" => "CAPTURE",
            "purchase_units" => [
                [
                    'reference_id' => $order->id,
                    "amount" => [
                        "currency_code" => Str::upper($order->currency),
                        "value" => $order->grand_total
                    ]
                ]
            ]
        ]);

        if (isset($response['id']) && $response['id'] != null) {
            return response()->json([
                'order' => $order->toPublic(),
                'orderID' => $response['id'],
                'successUrl' => route('payment.paypal.success', [
                    'key' => $order->key,
                    'redirect' => $request->redirect ?? app_url('/billing')
                ]),
            ], 200);
        } else {
            return response()->json([
                'error' => $response['message'] ?? 'Something went wrong.'
            ], 500);
        }
    }

    private function verifyPayment(Request $request): Order
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

                if ($orderable && method_exists($orderable, 'paymentConfirmation')) {
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

    public function wehbook(Request $request)
    {
        return response()->json([], 200);
    }
}
