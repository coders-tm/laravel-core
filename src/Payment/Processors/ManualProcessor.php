<?php

namespace Coderstm\Payment\Processors;

use Illuminate\Http\Request;
use Coderstm\Models\Shop\Checkout;
use Illuminate\Support\Facades\DB;
use Coderstm\Contracts\PaymentProcessorInterface;
use Coderstm\Models\Shop\Order;

class ManualProcessor extends AbstractPaymentProcessor implements PaymentProcessorInterface
{
    public function getProvider(): string
    {
        return 'manual';
    }

    public function setupPaymentIntent(Request $request, Checkout $checkout): array
    {
        // Manual payments don't need a setup intent
        return [
            'message' => 'Manual payment ready for processing',
            'amount' => $checkout->grand_total,
            'currency' => $checkout->currency ?? config('app.currency', 'USD'),
        ];
    }

    public function confirmPayment(Request $request, Checkout $checkout): array
    {
        $request->validate([
            'reference_number' => 'required|string',
            'payment_notes' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $order = $this->createOrderFromCheckout($checkout);

            $status = $order->has_due ? Order::STATUS_PENDING : Order::STATUS_PAID;

            $order->update([
                'payment_status' => $status,
                'status' => $status,
            ]);

            DB::commit();

            return [
                'success' => true,
                'order_id' => $order->key,
                'transaction_id' => $request->reference_number,
                'status' => 'pending_verification',
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
