<?php

namespace Coderstm\Payment\Processors;

use Illuminate\Http\Request;
use Coderstm\Models\Shop\Order;
use Coderstm\Models\Subscription;
use Coderstm\Models\Shop\Checkout;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Coderstm\Models\Subscription\Plan;
use Coderstm\Contracts\SubscriptionStatus;

abstract class AbstractPaymentProcessor
{
    /**
     * Create order from checkout - shared method for all processors
     */
    protected function createOrderFromCheckout(Checkout $checkout): Order
    {
        $user = Auth::guard('sanctum')->user();
        $orderData = [
            'source' => 'Checkout',
            'customer' => [
                'id' => $user->id ?? $checkout->user_id,
                'email' => $checkout->email,
                'first_name' => $checkout->first_name,
                'last_name' => $checkout->last_name,
                'phone_number' => $checkout->phone_number,
            ],
            'contact' => [
                'email' => $checkout->email,
                'phone_number' => $checkout->phone_number,
            ],
            'billing_address' => $checkout->billing_address,
            'shipping_address' => $checkout->shipping_address,
            'sub_total' => $checkout->sub_total,
            'tax_total' => $checkout->tax_total,
            'shipping_total' => $checkout->shipping_total ?? 0,
            'discount_total' => $checkout->discount_total ?? 0,
            'grand_total' => $checkout->grand_total,
            'currency' => $checkout->currency ?? config('app.currency', 'USD'),
            'line_items' => $checkout->line_items?->toArray(),
            'tax_lines' => $checkout->tax_lines?->toArray(),
            'discount' => $checkout->discount?->toArray(),
            'note' => $checkout->note,
            'status' => 'pending',
            'payment_status' => 'pending',
            'preserve_tax_calculations' => true,
            'checkout_id' => $checkout->id,
        ];

        $order = Order::modifyOrCreate($orderData);

        // Update checkout status instead of deleting it
        $checkout->update([
            'status' => 'completed',
            'order_id' => $order->id,
        ]);

        // Handle recurring product creation for subscription checkouts
        if ($checkout->type === 'subscription') {
            $this->createRecurringProductsFromOrder($order, $checkout);
        }

        return $order;
    }

    /**
     * Create recurring products from order line items
     */
    protected function createRecurringProductsFromOrder(Order $order, Checkout $checkout): void
    {
        foreach ($checkout->line_items as $lineItem) {
            // Check if this is a recurring product
            if (isset($lineItem['metadata']['plan_id'])) {
                try {
                    $planId = $lineItem['metadata']['plan_id'];
                    $recurringPlan = Plan::find($planId);
                    if (!$recurringPlan) {
                        Log::warning('Recurring plan not found for line item', [
                            'plan_id' => $planId,
                            'order_id' => $order->id,
                            'checkout_token' => $checkout->token,
                        ]);
                    }

                    $metadata = $lineItem['metadata'] ?? [];
                    $userId = Auth::guard('sanctum')->id() ?: $order->customer_id;
                    $status = $recurringPlan->hasTrial() ? SubscriptionStatus::TRIALING : SubscriptionStatus::ACTIVE;

                    // Create user instance for subscription
                    $user = \Coderstm\Coderstm::$subscriptionUserModel::find($userId);
                    if (!$user) {
                        Log::warning('User not found for subscription creation', [
                            'user_id' => $userId,
                            'order_id' => $order->id,
                            'checkout_token' => $checkout->token,
                        ]);
                    }

                    // Create the subscription
                    $subscription = $user->newSubscription('shop', $recurringPlan, $metadata)
                        ->withStatus($status);

                    $subscription->saveWithoutInvoice();

                    $order->update([
                        'orderable_type' => Subscription::class,
                        'orderable_id' => $subscription->id,
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to create subscription from AbstractPaymentProcessor', [
                        'error' => $e->getMessage(),
                        'order_id' => $order->id,
                        'checkout_token' => $checkout->token,
                        'line_item' => $lineItem,
                        'trace' => $e->getTraceAsString(),
                    ]);

                    // Don't fail the entire order creation, but log the error
                    // The user has already paid, so we need to handle this gracefully
                }
            } else {
                Log::error('Line item is not a recurring product', [
                    'line_item' => $lineItem,
                    'order_id' => $order->id,
                    'checkout_token' => $checkout->token,
                ]);
            }
        }
    }

    /**
     * Default implementation for success callback
     * Redirects to cart with success message
     * Override this in child classes for provider-specific behavior
     */
    public function handleSuccessCallback(Request $request): array
    {
        return [
            'success' => true,
            'redirect_url' => '/user/shop/cart',
            'message' => 'Payment completed successfully!'
        ];
    }

    /**
     * Default implementation for cancel callback
     * Redirects to checkout with cancellation message
     * Override this in child classes for provider-specific behavior
     */
    public function handleCancelCallback(Request $request): array
    {
        return [
            'success' => true,
            'redirect_url' => '/user/shop/checkout',
            'message' => 'Payment was cancelled. You can try again or choose a different payment method.'
        ];
    }
}
