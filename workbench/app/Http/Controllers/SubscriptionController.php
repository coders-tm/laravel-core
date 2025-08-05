<?php

namespace App\Http\Controllers;

use Coderstm\Coderstm;
use Coderstm\Traits\Helpers;
use Illuminate\Http\Request;
use Coderstm\Models\Subscription;
use Coderstm\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;
use Coderstm\Notifications\SubscriptionCancelNotification;

class SubscriptionController extends Controller
{
    use Helpers;

    /**
     * Display shop/product subscriptions for the authenticated user.
     */
    public function index(Request $request)
    {
        // Get all product-based subscriptions for the user
        $query = Subscription::where('user_id', user('id'))
            ->with('plan', 'plan.variant', 'plan.product')
            ->where('type', 'shop');

        // Apply status filter if present
        if ($request->query('status')) {
            if ($request->status === 'inactive') {
                $query->whereNotIn('status', ['active', 'trialing', 'pending']);
            } else {
                $statuses = array_map('trim', explode(',', $request->get('status')));
                $query->whereIn('status', $statuses);
            }
        }

        $subscriptions = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        // Transform subscriptions to include additional information
        $subscriptions->getCollection()->each(function ($subscription) {
            $upcomingInvoice = $subscription->upcomingInvoice();

            // Add status messages
            if ($subscription->canceled() && $subscription->onGracePeriod()) {
                $subscription['message'] = trans('messages.subscription.canceled', [
                    'date' => $subscription->ends_at->format('d M, Y')
                ]);
            } else if ($subscription->pastDue() || $subscription->hasIncompletePayment()) {
                $invoice = $subscription->latestInvoice;
                $amount = $invoice?->total();
                $subscription['message'] = trans('messages.subscription.past_due', [
                    'amount' => $amount
                ]);
                $subscription['invoice'] = [
                    'amount' => $amount,
                    'key' => $invoice?->key
                ];
                $subscription['hasDue'] = true;
            } else if ($upcomingInvoice) {
                $subscription['upcomingInvoice'] = [
                    'amount' => $upcomingInvoice->total(),
                    'date' => $upcomingInvoice->due_date->format('d M, Y'),
                ];

                if ($subscription->onTrial()) {
                    $trial_end = $subscription->trial_ends_at->format('M d, Y');
                    $subscription['message'] = "You're on trial until $trial_end. Once it ends, we'll charge you for your subscription.";
                } else {
                    $subscription['message'] = trans('messages.subscription.active', [
                        'amount' => $subscription['upcomingInvoice']['amount'],
                        'date' => $subscription['upcomingInvoice']['date']
                    ]);
                }
            }

            // Add computed properties
            $subscription['canceled'] = $subscription->canceled();
            $subscription['ended'] = $subscription->ended();
            $subscription['is_valid'] = $subscription->valid() ?? false;

            // Get current price based on intro pricing or regular pricing
            $subscription['current_price'] = $subscription->getCurrentPrice();

            // Clean up relations to avoid circular references
            $subscription->unsetRelation('owner');
        });

        return response()->json($subscriptions, 200);
    }

    /**
     * Show a specific subscription.
     */
    public function show(Request $request, Subscription $subscription)
    {
        // Ensure the subscription belongs to the user and is product-based
        if ($subscription->user_id !== user('id') || !$subscription->isProductBased()) {
            throw ValidationException::withMessages([
                'subscription' => 'Subscription not found or access denied.',
            ]);
        }

        $subscription->load(['plan.product', 'plan.variant']);
        $upcomingInvoice = $subscription->upcomingInvoice();

        // Add status information
        if ($subscription->canceled() && $subscription->onGracePeriod()) {
            $subscription['message'] = trans('messages.subscription.canceled', [
                'date' => $subscription->ends_at->format('d M, Y')
            ]);
        } else if ($subscription->pastDue() || $subscription->hasIncompletePayment()) {
            $invoice = $subscription->latestInvoice;
            $amount = $invoice?->total();
            $subscription['message'] = trans('messages.subscription.past_due', [
                'amount' => $amount
            ]);
            $subscription['invoice'] = [
                'amount' => $amount,
                'key' => $invoice?->key
            ];
            $subscription['hasDue'] = true;
        } else if ($upcomingInvoice) {
            $subscription['upcomingInvoice'] = [
                'amount' => $upcomingInvoice->total(),
                'date' => $upcomingInvoice->due_date->format('d M, Y'),
            ];
        }

        $subscription['canceled'] = $subscription->canceled();
        $subscription['ended'] = $subscription->ended();
        $subscription['is_valid'] = $subscription->valid() ?? false;
        $subscription['current_price'] = $subscription->getCurrentPrice();

        $subscription->unsetRelation('owner');
        $subscription->unsetRelation('user');

        return response()->json($subscription, 200);
    }

    /**
     * Cancel a product subscription.
     */
    public function cancel(Request $request, Subscription $subscription)
    {
        $user = $this->user();

        // Ensure the subscription belongs to the user and is product-based
        if ($subscription->user_id !== $user->id || !$subscription->isProductBased()) {
            throw ValidationException::withMessages([
                'subscription' => 'Subscription not found or access denied.',
            ]);
        }

        try {
            $subscription->cancel();

            event(new \Coderstm\Events\SubscriptionCancel($subscription));

            $user->notify(new SubscriptionCancelNotification($subscription));

            return response()->json([
                'message' => trans('messages.subscription.cancel'),
                'data' => $subscription->fresh()
            ], 200);
        } catch (\Exception $e) {
            throw ValidationException::withMessages([
                'subscription' => 'Failed to cancel subscription: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Resume a canceled product subscription.
     */
    public function resume(Request $request, Subscription $subscription)
    {
        $user = $this->user();

        // Ensure the subscription belongs to the user and is product-based
        if ($subscription->user_id !== $user->id || !$subscription->isProductBased()) {
            throw ValidationException::withMessages([
                'subscription' => 'Subscription not found or access denied.',
            ]);
        }

        try {
            $subscription = $subscription->resume();

            event(new \Coderstm\Events\SubscriptionResume($subscription));

            return response()->json([
                'message' => trans('messages.subscription.resume'),
                'data' => $subscription->fresh()
            ], 200);
        } catch (\Exception $e) {
            throw ValidationException::withMessages([
                'subscription' => 'Failed to resume subscription: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Pay for a past due subscription.
     */
    public function pay(Request $request, Subscription $subscription)
    {
        $user = $this->user();

        // Ensure the subscription belongs to the user and is product-based
        if ($subscription->user_id !== $user->id || !$subscription->isProductBased()) {
            throw ValidationException::withMessages([
                'subscription' => 'Subscription not found or access denied.',
            ]);
        }

        $request->validate([
            'payment_method' => 'required|exists:payment_methods,id',
        ]);

        try {
            $subscription->pay($request->payment_method);

            return response()->json([
                'data' => $subscription->fresh(),
                'message' => trans('messages.subscription.due_payment')
            ], 200);
        } catch (\Exception $e) {
            throw ValidationException::withMessages([
                'payment' => 'Failed to process payment: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Get all active product subscriptions for admin.
     */
    public function adminIndex(Request $request)
    {
        // This method is for admin use only
        if (!is_admin()) {
            throw ValidationException::withMessages([
                'access' => 'Access denied.',
            ]);
        }

        $query = Subscription::where('type', 'shop')
            ->with(['user', 'plan.product', 'plan.variant']);

        // Apply filters
        if ($request->has('status')) {
            switch ($request->status) {
                case 'active':
                    $query->where('status', 'active');
                    break;
                case 'canceled':
                    $query->where('status', 'canceled');
                    break;
                case 'past_due':
                    $query->where('status', 'past_due');
                    break;
                case 'incomplete':
                    $query->where('status', 'incomplete');
                    break;
            }
        }

        if ($request->has('product_id')) {
            $query->whereHas('plan', function ($q) use ($request) {
                $q->where('product_id', $request->product_id);
            });
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $subscriptions = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json($subscriptions, 200);
    }

    /**
     * Get the authenticated user.
     */
    protected function user()
    {
        if (request()->filled('user_id') && is_admin()) {
            return Coderstm::$userModel::findOrFail(request()->user_id);
        }

        return user();
    }
}
