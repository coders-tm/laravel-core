<?php

namespace Coderstm\Http\Controllers;

use Coderstm\Coderstm;
use Coderstm\Models\PaymentMethod;
use Coderstm\Payment\Processor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function status(string $token)
    {
        $order = Coderstm::$orderModel::where('key', $token)->with(['line_items', 'tax_lines', 'discount', 'contact', 'customer'])->firstOrFail();
        $orderData = $order->toArray();
        $paymentMethods = PaymentMethod::enabled()->orderBy('order')->get()->pluck('provider')->values();

        return response()->json(array_merge($orderData, ['payment_methods' => $paymentMethods]));
    }

    public function setupPaymentIntent(Request $request)
    {
        $request->validate(['token' => 'required|string|exists:'.Coderstm::$orderModel.',key', 'provider' => 'required|integer|exists:'.PaymentMethod::class.',id']);
        try {
            $order = Coderstm::$orderModel::where('key', $request->token)->firstOrFail();
            $paymentMethod = PaymentMethod::findOrFail($request->provider);
            if ($order->payment_status === 'paid') {
                return response()->json(['message' => 'This order has already been paid', 'order_number' => $order->formated_id], 422);
            }
            $provider = $paymentMethod->integration_via ?? $paymentMethod->provider;
            if (! Processor::isSupported($provider)) {
                return response()->json(['message' => 'Payment method not supported', 'provider' => $provider], 422);
            }
            $processor = Processor::make($provider);
            $processor->setPaymentMethod($paymentMethod);
            $payable = \Coderstm\Payment\Payable::fromOrder($order);
            $paymentIntent = $processor->setupPaymentIntent($request, $payable);

            return response()->json($paymentIntent);
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    public function confirmPayment(Request $request)
    {
        $request->validate(['token' => 'required|string|exists:'.Coderstm::$orderModel.',key', 'provider' => 'required|integer|exists:'.PaymentMethod::class.',id']);
        try {
            $order = Coderstm::$orderModel::where('key', $request->token)->firstOrFail();
            $paymentMethod = PaymentMethod::findOrFail($request->provider);
            $provider = $paymentMethod->integration_via ?? $paymentMethod->provider;
            if ($order->payment_status === 'paid') {
                return response()->json(['success' => true, 'message' => 'This order has already been paid', 'order_number' => $order->formated_id, 'order_id' => $order->key]);
            }
            if (! Processor::isSupported($provider)) {
                return response()->json(['message' => 'Payment method not supported', 'provider' => $provider], 422);
            }
            $processor = Processor::make($provider);
            $processor->setPaymentMethod($paymentMethod);
            $payable = \Coderstm\Payment\Payable::fromOrder($order);
            $result = $processor->confirmPayment($request, $payable);
            if ($paymentData = $result->getPaymentData()) {
                $order->markAsPaid($paymentData, ['amount' => $order->grand_total]);
            }

            return response()->json(['success' => true, 'order_id' => $order->key, 'transaction_id' => $result->getTransactionId(), 'status' => $result->getStatus() ?? 'success']);
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    public function handleSuccess(Request $request, string $provider)
    {
        $redirectUrl = '/billing';
        try {
            $result = Processor::handleSuccessCallback($provider, $request);
            $payment = $result->payment;
            if ($order = $payment->paymentable) {
                if (isset($order->key)) {
                    $redirectUrl = "/payment/{$order->key}";
                }
                if ($order->payment_status === 'paid') {
                    return redirect($redirectUrl)->with('info', 'This order has already been paid');
                } else {
                    $order->markAsPaid();
                }
            }

            return redirect($redirectUrl)->with($result->getMessageType(), $result->getMessage());
        } catch (\Throwable $e) {
            Log::error("Order payment success handler error for provider {$provider}: ".$e->getMessage(), ['request' => $request->all(), 'provider' => $provider, 'error' => $e->getMessage()]);

            return redirect($redirectUrl)->with('info', 'Payment may have been completed. Please check your order status or contact support if needed.');
        }
    }

    public function handleCancel(Request $request, string $provider)
    {
        $redirectUrl = '/orders';
        try {
            $result = Processor::handleCancelCallback($provider, $request);
            $payment = $result->payment;
            if ($paymentable = $payment->paymentable) {
                if (isset($paymentable->key)) {
                    $redirectUrl = "/payment/{$paymentable->key}";
                }
            }

            return redirect($redirectUrl)->with($result->getMessageType(), $result->getMessage());
        } catch (\Throwable $e) {
            Log::error("Order payment cancel handler error for provider {$provider}: ".$e->getMessage(), ['request' => $request->all(), 'provider' => $provider, 'error' => $e->getMessage()]);

            return redirect($redirectUrl)->with('info', 'Payment process was interrupted. Please try again.');
        }
    }
}
