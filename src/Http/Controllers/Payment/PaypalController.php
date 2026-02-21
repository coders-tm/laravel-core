<?php

namespace Coderstm\Http\Controllers\Payment;

use Coderstm\Coderstm;
use Coderstm\Http\Controllers\Controller;
use Coderstm\Models\Shop\Order;
use Coderstm\Traits\Paymentable;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PaypalController extends Controller
{
    use Paymentable;

    public function token(Request $request)
    {
        $request->validate(['key' => 'required|string']);
        $order = Order::findByKey($request->key)->load('customer');
        $response = Coderstm::paypal()->createOrder(['intent' => 'CAPTURE', 'purchase_units' => [['reference_id' => $order->id, 'amount' => ['currency_code' => Str::upper($order->currency), 'value' => (string) round($order->grand_total, 2)]]]]);
        if (isset($response['id']) && $response['id'] != null) {
            return response()->json(['order' => $order->toPublic(), 'orderID' => $response['id'], 'successUrl' => route('payment.paypal.success', ['key' => $order->key, 'redirect' => $request->redirect ?? app_url('/billing')])], 200);
        } else {
            return response()->json(['response' => $response, 'error' => $response['error']['message'] ?? 'Something went wrong.'], 500);
        }
    }

    private function verifyPayment(Request $request): Order
    {
        $request->validate(['key' => 'required|string', 'orderID' => 'required|string', 'paymentID' => 'required|string']);
        $orderID = $request->orderID;
        $paymentID = $request->paymentID;
        $order = Order::findByKey($request->key)->load('orderable');
        $orderable = $order->orderable;
        try {
            $payment = Coderstm::paypal()->capturePaymentOrder($orderID);
            if (isset($payment['status']) && $payment['status'] == 'COMPLETED') {
                $order->markAsPaid(config('paypal.id'), ['id' => $paymentID, 'amount' => $order->due_amount, 'status' => $payment['status']]);
            } else {
            }

            return $order;
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    public function webhook(Request $request)
    {
        return response()->json([], 200);
    }
}
