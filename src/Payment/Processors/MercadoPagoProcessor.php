<?php

namespace Coderstm\Payment\Processors;

use Coderstm\Coderstm;
use Illuminate\Http\Request;
use Coderstm\Models\Shop\Checkout;
use Coderstm\Models\PaymentMethod;
use Illuminate\Support\Facades\DB;
use Coderstm\Payment\Mappers\MercadoPagoPayment;
use Coderstm\Contracts\PaymentProcessorInterface;

class MercadoPagoProcessor extends AbstractPaymentProcessor implements PaymentProcessorInterface
{
    protected PaymentMethod $paymentMethod;

    public function __construct()
    {
        $this->paymentMethod = PaymentMethod::where('provider', 'mercadopago')->where('active', true)->firstOrFail();
    }

    public function getProvider(): string
    {
        return 'mercadopago';
    }

    public function setupPaymentIntent(Request $request, Checkout $checkout): array
    {
        $mercadopago = Coderstm::mercadopago();

        // Create preference for checkout
        $preference = $mercadopago->createPaymentIntent([
            'items' => [
                [
                    'title' => "Order #{$checkout->token}",
                    'description' => "Payment for order {$checkout->token}",
                    'quantity' => 1,
                    'unit_price' => $checkout->grand_total,
                    'currency_id' => strtoupper($checkout->currency ?? 'USD'),
                ]
            ],
            'payer' => [
                'email' => $checkout->email,
                'name' => $checkout->first_name,
                'surname' => $checkout->last_name,
                'phone' => [
                    'number' => $checkout->phone_number ?? '',
                ],
                'address' => [
                    'street_name' => $checkout->billing_address['line1'] ?? '',
                    'street_number' => '',
                    'zip_code' => $checkout->billing_address['postal_code'] ?? '',
                ]
            ],
            'back_urls' => [
                'success' => route('shop.checkout.success', ['provider' => 'mercadopago']),
                'failure' => route('shop.checkout.cancel', ['provider' => 'mercadopago']),
                'pending' => route('shop.checkout.success', ['provider' => 'mercadopago']),
            ],
            'auto_return' => 'approved',
            'external_reference' => $checkout->token,
            'notification_url' => config('app.url') . '/webhooks/mercadopago',
            'statement_descriptor' => config('app.name', 'Purchase'),
        ]);

        return [
            'preference_id' => $preference->id,
            'init_point' => $preference->init_point,
            'sandbox_init_point' => $preference->sandbox_init_point,
        ];
    }

    public function confirmPayment(Request $request, Checkout $checkout): array
    {
        $request->validate([
            'payment_id' => 'required|string',
        ]);

        DB::beginTransaction();

        try {
            $mercadopago = Coderstm::mercadopago();

            // Get payment details from MercadoPago
            $payment = $mercadopago->confirmPayment($request->payment_id);

            // Check if payment is approved
            if ($payment->status !== 'approved') {
                throw new \Exception("Payment not approved. Status: {$payment->status}");
            }

            $order = $this->createOrderFromCheckout($checkout);

            $paymentData = MercadoPagoPayment::fromResponse(
                [
                    'id' => $payment->id,
                    'status' => $payment->status,
                    'status_detail' => $payment->status_detail ?? null,
                ],
                $this->paymentMethod->id,
                $checkout->grand_total,
                $checkout->currency ?? config('app.currency')
            );

            $order->markAsPaid($paymentData);

            DB::commit();

            return [
                'success' => true,
                'status' => 'success',
                'payment_id' => $payment->id,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
