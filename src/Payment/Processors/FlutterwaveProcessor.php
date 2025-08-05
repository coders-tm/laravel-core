<?php

namespace Coderstm\Payment\Processors;

use Coderstm\Coderstm;
use Illuminate\Http\Request;
use Coderstm\Models\Shop\Checkout;
use Coderstm\Models\PaymentMethod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Coderstm\Payment\Mappers\FlutterwavePayment;
use Coderstm\Contracts\PaymentProcessorInterface;

class FlutterwaveProcessor extends AbstractPaymentProcessor implements PaymentProcessorInterface
{
    protected PaymentMethod $paymentMethod;

    public function __construct()
    {
        $this->paymentMethod = PaymentMethod::where('provider', 'flutterwave')->where('active', true)->firstOrFail();
    }

    public function getProvider(): string
    {
        return 'flutterwave';
    }

    public function validateCallback(Request $request): ?string
    {
        // Flutterwave sends tx_ref (which is our checkout token) in the callback
        $checkoutToken = $request->get('tx_ref') ?? $request->get('checkout_token');

        // Additional validation can be added here (signature verification, etc.)
        if ($checkoutToken) {
            Log::info('Flutterwave callback validation successful', [
                'tx_ref' => $request->get('tx_ref'),
                'transaction_id' => $request->get('transaction_id'),
                'status' => $request->get('status'),
            ]);
        }

        return $checkoutToken;
    }

    /**
     * Setup payment intent for Flutterwave
     */
    public function setupPaymentIntent(Request $request, Checkout $checkout): array
    {
        $flutterwave = Coderstm::flutterwave();

        if (!$flutterwave) {
            throw new \Exception('Flutterwave client not configured');
        }

        try {
            // Generate unique transaction reference
            $txRef = 'FLW_' . $checkout->token . '_' . time();

            // Prepare customer data
            $customerData = $request->input('customer_data', []);

            $payload = [
                'tx_ref' => $txRef,
                'amount' => $checkout->grand_total,
                'currency' => strtoupper($checkout->currency ?? config('app.currency', 'NGN')),
                'redirect_url' => route('shop.checkout.success', ['provider' => 'flutterwave']),
                'payment_options' => 'card,banktransfer,ussd,account',
                'customer' => [
                    'email' => $checkout->email,
                    'phonenumber' => $checkout->phone_number,
                    'name' => $checkout->first_name . ' ' . $checkout->last_name,
                ],
                'customizations' => [
                    'title' => config('app.name') . ' Payment',
                    'description' => 'Order #' . $checkout->token,
                    'logo' => config('app.logo'),
                ],
                'meta' => [
                    'checkout_token' => $checkout->token,
                    'payment_method_id' => $this->paymentMethod->id,
                ],
            ];

            // Initialize payment with Flutterwave SDK using correct method names
            $paymentUrl = $flutterwave->setAmount($payload['amount'])
                ->setCurrency($payload['currency'])
                ->setEmail($payload['customer']['email'])
                ->setFirstname(explode(' ', $payload['customer']['name'])[0])
                ->setLastname(explode(' ', $payload['customer']['name'], 2)[1] ?? '')
                ->setPhoneNumber($payload['customer']['phonenumber'])
                ->setTitle($payload['customizations']['title'])
                ->setDescription($payload['customizations']['description'])
                ->setRedirectUrl($payload['redirect_url'])
                ->setMetaData($payload['meta'])
                ->initialize();

            if ($paymentUrl) {
                return [
                    'success' => true,
                    'payment_url' => $paymentUrl,
                    'tx_ref' => $txRef,
                    'checkout_token' => $checkout->token,
                    'provider' => 'flutterwave',
                ];
            }

            throw new \Exception('Failed to initialize Flutterwave payment');
        } catch (\Exception $e) {
            throw new \Exception('Flutterwave payment setup failed: ' . $e->getMessage());
        }
    }

    /**
     * Confirm payment completion for Flutterwave
     */
    public function confirmPayment(Request $request, Checkout $checkout): array
    {
        $flutterwave = Coderstm::flutterwave();

        if (!$flutterwave) {
            throw new \Exception('Flutterwave client not configured');
        }

        try {
            $txRef = $request->input('tx_ref');
            $transactionId = $request->input('transaction_id');

            if (!$txRef && !$transactionId) {
                throw new \Exception('Transaction reference or ID required');
            }

            // Verify transaction with Flutterwave SDK
            if ($transactionId) {
                $response = $flutterwave->requeryTransaction($transactionId);
            } elseif ($txRef) {
                $response = $flutterwave->requeryTransaction($txRef);
            }

            if (!$response || $response['status'] !== 'success') {
                throw new \Exception('Payment verification failed');
            }

            $transactionData = $response['data'];

            // Verify payment amount and currency
            if ($transactionData['amount'] != $checkout->grand_total) {
                throw new \Exception('Payment amount mismatch');
            }

            if (strtoupper($transactionData['currency']) !== strtoupper($checkout->currency ?? config('app.currency', 'NGN'))) {
                throw new \Exception('Payment currency mismatch');
            }

            // Check if payment is successful
            if (strtolower($transactionData['status']) !== 'successful') {
                throw new \Exception('Payment not successful: ' . $transactionData['status']);
            }

            return DB::transaction(function () use ($checkout, $transactionData) {
                // Create payment record
                $payment = FlutterwavePayment::fromResponse(
                    $transactionData,
                    $this->paymentMethod->id,
                    $checkout->grand_total,
                    $checkout->currency
                );

                // Process the order
                $order = $checkout->processOrder([
                    'payment_method_id' => $this->paymentMethod->id,
                    'payment_status' => 'paid',
                    'transaction_id' => $payment->getTransactionId(),
                    'gateway_response' => $transactionData,
                ]);

                return [
                    'success' => true,
                    'payment' => $payment,
                    'order' => $order,
                    'transaction' => $transactionData,
                ];
            });
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Handle webhook notifications from Flutterwave
     */
    public function handleWebhook(Request $request): array
    {
        try {
            $payload = $request->all();

            // Verify webhook signature if configured
            $secretHash = config('flutterwave.webhook_hash');
            if ($secretHash) {
                $signature = $request->header('verif-hash');
                if (!$signature || $signature !== $secretHash) {
                    throw new \Exception('Invalid webhook signature');
                }
            }

            if ($payload['event'] === 'charge.completed') {
                $transactionData = $payload['data'];

                // Find checkout by transaction reference
                $checkoutToken = $transactionData['meta']['checkout_token'] ?? null;
                if ($checkoutToken) {
                    $checkout = Checkout::where('token', $checkoutToken)->first();
                    if ($checkout && !$checkout->isProcessed()) {
                        // Process the payment
                        $this->confirmPayment(new Request([
                            'transaction_id' => $transactionData['id'],
                        ]), $checkout);
                    }
                }
            }

            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
