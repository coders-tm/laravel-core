<?php

namespace Coderstm\Http\Controllers\Subscription;

use Coderstm\Coderstm;
use Coderstm\Events\SubscriptionPlanChanged;
use Coderstm\Http\Controllers\Controller;
use Coderstm\Models\Coupon;
use Coderstm\Models\PaymentMethod;
use Coderstm\Models\Redeem;
use Coderstm\Models\Subscription;
use Coderstm\Models\Subscription\Plan;
use Coderstm\Notifications\SubscriptionCancelNotification;
use Coderstm\Notifications\SubscriptionDowngradeNotification;
use Coderstm\Notifications\SubscriptionUpgradeNotification;
use Coderstm\Services\GatewaySubscriptionFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Validation\ValidationException;

class SubscriptionController extends Controller
{
    public function index(Request $request)
    {
        $relations = ['plan', 'plan.variant', 'plan.product', 'latestInvoice'];
        if (is_admin()) {
            $relations[] = 'user';
        }
        $query = Coderstm::$subscriptionModel::with($relations);
        if (is_user()) {
            $query->where('user_id', user('id'))->orderBy('type');
        } elseif ($request->filled('user')) {
            $query->where('user_id', $request->user);
        }
        if ($request->filled('product')) {
            $query->whereHas('plan', function ($q) use ($request) {
                $q->where('product_id', $request->product);
            });
        }
        if ($request->query('status')) {
            if ($request->status === 'inactive') {
                $query->whereNotIn('status', ['active', 'trialing']);
            } else {
                $statuses = array_map('trim', explode(',', $request->get('status')));
                $query->whereIn('status', $statuses);
            }
        }
        $subscriptions = $query->orderBy($request->sortBy ?? 'created_at', $request->direction ?? 'desc')->paginate($request->rowsPerPage ?: 15);
        $subscriptions->getCollection()->transform(function ($subscription) {
            return (new Subscription)->forceFill($this->transformSubscription($subscription));
        });

        return new ResourceCollection($subscriptions);
    }

    public function current(Request $request)
    {
        $user = $this->user($request);
        $subscription = $user->subscription();
        if ($user->onGenericTrial()) {
            return response()->json(['on_generic_trial' => true, 'trial_ends_at' => $user->trial_ends_at], 200);
        } elseif ($user->is_free_forever || ! $subscription) {
            return response()->json(['is_free_forever' => (bool) $user->is_free_forever], 200);
        }
        $data = $subscription->toResponse(['plan', 'usages', 'next_plan']);

        return response()->json($data, 200);
    }

    public function subscribe(Request $request)
    {
        $request->validate(['plan' => 'required|exists:plans,id', 'payment_method' => 'required|exists:payment_methods,id'], ['payment_method.required_unless' => __('Please select a payment method to proceed with the subscription.')]);
        $user = $this->user($request);
        $upgrade = $downgrade = false;
        $paymentMethod = PaymentMethod::find($request->payment_method);
        $provider = $paymentMethod?->provider;
        if ($request->filled('subscription')) {
            $subscription = Subscription::find($request->subscription);
        } else {
            $subscription = $user->subscription();
        }
        $plan = Plan::find($request->plan);
        $subscribed = $user->subscribed();
        $trial_end = $user->trial_ends_at;
        $trial_days = $plan->trial_days;
        $coupon = optional(Coupon::findByCode($request->promotion_code));
        $oldPlan = null;
        if ($subscribed && $subscription->plan_id == $plan->id) {
            throw ValidationException::withMessages(['plan' => __('You already subscribed to :plan plan.', ['plan' => $plan->label])]);
        }
        try {
            if ($subscription) {
                $downgrade = $plan->price < $subscription->plan->price;
                if ($downgrade) {
                    $this->downgrade($subscription, ['plan' => $plan->id]);
                } else {
                    $oldPlan = $subscription->plan;
                    $subscription = $subscription->withCoupon($request->promotion_code)->swap($plan->id);
                    $upgrade = true;
                }
            } else {
                $subscription = $user->newSubscription('default', $plan->id)->withCoupon($request->promotion_code)->setProvider($provider);
                if ($trial_end && $trial_end->isFuture()) {
                    $subscription->trialUntil($trial_end);
                } elseif ($trial_days && ! $trial_end) {
                    $subscription->trialDays($trial_days);
                }
                $subscription->saveAndInvoice();
            }
        } catch (\Throwable $e) {
            throw $e;
        } finally {
            if ($coupon->id && $subscription) {
                Redeem::updateOrCreate(['redeemable_type' => get_class($subscription), 'redeemable_id' => $subscription->id, 'coupon_id' => $coupon->id], ['user_id' => $user->id, 'amount' => $coupon->getAmount($plan->price)]);
            }
            if ($upgrade) {
                $subscription->syncOrResetUsages();
                $subscription->oldPlan = $oldPlan;
                $subscription->plan = $plan;
                event(new \Coderstm\Events\SubscriptionUpgraded($subscription));
                $user->notify(new SubscriptionUpgradeNotification($subscription));
            }
            $gateway = GatewaySubscriptionFactory::make($subscription);

            return response()->json($gateway->setup(), 200);
        }
    }

    public function pay(Request $request, $id)
    {
        $request->validate(['payment_method' => 'required|exists:payment_methods,id']);
        $subscription = Coderstm::$subscriptionModel::findOrFail($id);
        $isOutstanding = $subscription->hasIncompletePayment() || $subscription->onGracePeriod();
        $subscription->pay($request->payment_method);
        if ($isOutstanding) {
            $message = __('Outstanding payment processed. Subscription reactivated.');
        } else {
            $message = __('Renewal payment processed successfully.');
        }

        return response()->json(['data' => $this->transformSubscription($subscription->fresh()), 'message' => $message], 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate(['plan' => 'required|integer', 'user' => 'required|integer', 'starts_at' => 'nullable|date', 'expires_at' => 'nullable|date', 'trial_days' => 'nullable|integer|min:0', 'promotion_code' => 'nullable|string', 'force' => 'sometimes|boolean', 'mark_as_paid' => 'sometimes|boolean', 'payment_method' => 'nullable|integer']);
        try {
            $user = Coderstm::$userModel::findOrFail($validated['user']);
        } catch (\Exception) {
            throw ValidationException::withMessages(['user' => __('The specified user does not exist.')]);
        }
        $service = app(\Coderstm\Services\Admin\SubscriptionCreationService::class);
        $subscription = $service->createOrUpdate($user, $validated);

        return response()->json(['message' => __('Subscription has been created successfully.'), 'data' => $this->transformSubscription($subscription)], 201);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate(['plan' => 'required|integer', 'starts_at' => 'nullable|date', 'expires_at' => 'nullable|date', 'trial_days' => 'nullable|integer|min:0', 'promotion_code' => 'nullable|string', 'force' => 'sometimes|boolean', 'mark_as_paid' => 'sometimes|boolean', 'payment_method' => 'nullable|integer']);
        try {
            $subscription = Coderstm::$subscriptionModel::findOrFail($id);
        } catch (\Exception) {
            throw ValidationException::withMessages(['id' => __('The specified subscription does not exist.')]);
        }
        $service = app(\Coderstm\Services\Admin\SubscriptionCreationService::class);
        $subscription = $service->createOrUpdate($subscription->user, $validated, $subscription);

        return response()->json(['message' => __('Subscription has been updated successfully.'), 'data' => $this->transformSubscription($subscription)], 200);
    }

    public function show(Request $request, $id)
    {
        $subscription = $this->resolveSubscription($id);
        $this->assertUserSubscriptionAccess($subscription);
        $subscription->load(['plan.product', 'plan.variant']);
        $data = $this->transformSubscription($subscription);

        return response()->json($data, 200);
    }

    public function cancel(Request $request, $id)
    {
        $subscription = $this->resolveSubscription($id);
        if (is_user()) {
            $this->assertUserSubscriptionAccess($subscription);
            if ($subscription->isContract()) {
                throw ValidationException::withMessages(['subscription' => __('Contract subscriptions cannot be cancelled until the end of their term. Please contact support for assistance.')]);
            }
        }
        $subscription->cancel();
        $subscription->cancelOpenInvoices();
        $user = $subscription->user;
        event(new \Coderstm\Events\SubscriptionCancel($subscription));
        if ($user) {
            $user->notify(new SubscriptionCancelNotification($subscription));
        }

        return response()->json(['message' => __('Subscription cancelled successfully.'), 'data' => $this->transformSubscription($subscription->fresh())], 200);
    }

    public function resume(Request $request, $id)
    {
        $subscription = $this->resolveSubscription($id);
        $this->assertUserSubscriptionAccess($subscription);
        try {
            $subscription = $subscription->resume();
            event(new \Coderstm\Events\SubscriptionResume($subscription));

            return response()->json(['message' => __('Subscription resumed successfully.'), 'data' => $this->transformSubscription($subscription->fresh())], 200);
        } catch (\Throwable $e) {
            throw ValidationException::withMessages(['subscription' => 'Failed to resume subscription: '.$e->getMessage()]);
        }
    }

    public function cancelDowngrade(Request $request, $id)
    {
        $subscription = $this->resolveSubscription($id);
        $this->assertUserSubscriptionAccess($subscription);
        $subscription->cancelDowngrade();

        return response()->json(['message' => __('Subscription downgrade cancelled successfully.'), 'data' => $this->transformSubscription($subscription->fresh())], 200);
    }

    public function renew(Request $request, $id)
    {
        $subscription = Coderstm::$subscriptionModel::findOrFail($id);
        if ($subscription->user_id !== user('id')) {
            throw ValidationException::withMessages(['subscription' => __('Subscription not found or access denied.')]);
        }
        if (! $subscription->hasIncompletePayment()) {
            throw ValidationException::withMessages(['subscription' => __('This subscription does not have any incomplete payments that require renewal.')]);
        }
        $latestInvoice = $subscription->latestInvoice;
        if ($latestInvoice && ! $latestInvoice->is_paid) {
            return response()->json(['redirect' => true, 'token' => $latestInvoice->key, 'amount' => $latestInvoice->total(), 'redirect_url' => user_route("payment/{$latestInvoice->key}"), 'message' => __('Please complete the pending payment to renew your subscription')], 200);
        } else {
            throw ValidationException::withMessages(['subscription' => 'No outstanding invoice found for this subscription.']);
        }
    }

    public function invoices(Request $request, $id)
    {
        $subscription = $this->resolveSubscription($id);
        $this->assertUserSubscriptionAccess($subscription);
        $query = $subscription->invoices();
        if ($request->filled('status')) {
            $query->whereStatus($request->status);
        }
        $invoices = $query->orderBy($request->sortBy ?? 'created_at', $request->direction ?? 'desc')->paginate($request->rowsPerPage ?: 15);

        return new ResourceCollection($invoices);
    }

    public function checkPromoCode(Request $request)
    {
        $request->validate(['promotion_code' => 'required|string', 'plan_id' => 'required']);
        $planId = $request->input('plan_id');
        $couponCode = $request->input('promotion_code');
        $coupon = Coupon::where('promotion_code', $couponCode)->first();
        if (! $coupon) {
            throw ValidationException::withMessages(['promotion_code' => ['Invalid coupon code']]);
        }
        if (! $coupon->isActive()) {
            throw ValidationException::withMessages(['promotion_code' => ['Coupon is not active']]);
        }
        if ($coupon->isExpired()) {
            throw ValidationException::withMessages(['promotion_code' => ['Coupon has expired']]);
        }
        if (! $coupon->canApplyToPlan($planId)) {
            throw ValidationException::withMessages(['promotion_code' => ['Invalid coupon code']]);
        }
        if ($coupon->checkMaxRedemptions()) {
            throw ValidationException::withMessages(['promotion_code' => [__('Coupon has reached maximum redemptions')]]);
        }

        return response()->json($coupon->toPublic());
    }

    protected function downgrade($subscription, array $options = [])
    {
        if (! isset($options['plan']) || ! $options['plan']) {
            throw ValidationException::withMessages(['plan' => __('A valid plan is necessary for downgrading the subscription.')]);
        }
        try {
            $oldPlan = Plan::find($subscription->plan_id);
            $newPlan = Plan::find($options['plan']);
            $user = $subscription->user;
            $subscription->is_downgrade = true;
            $subscription->next_plan = $options['plan'];
            $subscription->save();
            $subscription->oldPlan = $oldPlan;
            $subscription->plan = $newPlan;
            event(new SubscriptionPlanChanged($subscription, $oldPlan, $newPlan));
            if ($user) {
                $user->notify(new SubscriptionDowngradeNotification($subscription));
            }
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    protected function resolveSubscription($id)
    {
        return Coderstm::$subscriptionModel::findOrFail($id);
    }

    protected function assertUserSubscriptionAccess($subscription)
    {
        if (is_user() && $subscription->user_id != user('id')) {
            throw ValidationException::withMessages(['subscription' => __('You do not have access to this subscription.')]);
        }
    }

    protected function transformSubscription($subscription, array $extends = ['usages', 'plan', 'next_plan']): array
    {
        if (is_admin()) {
            $extends[] = 'user';
        }

        return $subscription->toResponse($extends);
    }

    protected function user(Request $request)
    {
        if (is_admin()) {
            return Coderstm::$userModel::find($request->input('user'));
        }

        return user();
    }
}
