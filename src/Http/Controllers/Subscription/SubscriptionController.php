<?php

namespace Coderstm\Http\Controllers\Subscription;

use Carbon\Carbon;
use Coderstm\Coderstm;
use Coderstm\Events\SubscriptionCancel;
use Coderstm\Events\SubscriptionPlanChanged;
use Coderstm\Events\SubscriptionResume;
use Coderstm\Events\SubscriptionUpgraded;
use Coderstm\Http\Controllers\Controller;
use Coderstm\Models\Coupon;
use Coderstm\Models\Redeem;
use Coderstm\Models\Subscription;
use Coderstm\Models\Subscription\Plan;
use Coderstm\Notifications\SubscriptionCancelNotification;
use Coderstm\Notifications\SubscriptionDowngradeNotification;
use Coderstm\Notifications\SubscriptionUpgradeNotification;
use Coderstm\Services\Admin\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Validation\ValidationException;

class SubscriptionController extends Controller
{
    public function index(Request $request)
    {
        $relations = ['plan', 'latestInvoice'];
        $query = Coderstm::$subscriptionModel::with($relations);
        if ($request->filled('user')) {
            $query->where('user_id', $request->user);
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
        $request->validate(['plan' => 'required|exists:plans,id']);
        $user = $this->user($request);
        $upgrade = $downgrade = false;
        if ($request->filled('subscription')) {
            $subscription = Subscription::find($request->subscription);
        } else {
            $subscription = $user->subscription();
        }
        $plan = Plan::find($request->plan);
        $subscribed = $user->subscribed();
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
                $subscription = $user->newSubscription('default', $plan->id)->withCoupon($request->promotion_code);
                if ($trial_days) {
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
                event(new SubscriptionUpgraded($subscription));
                $user->notify(new SubscriptionUpgradeNotification($subscription));
            }
            $invoice = $subscription?->latestInvoice;
            $payment = $invoice && $invoice->due_amount > 0;
            $redirectUrl = $payment ? user_route('/payment/'.$invoice->key, ['redirect' => user_route('/billing')]) : null;

            return response()->json(array_filter(['data' => $subscription?->toResponse(['usages', 'next_plan', 'plan']), 'redirect_url' => $redirectUrl, 'message' => trans_choice('messages.subscription.success', $payment ? 1 : 0, ['plan' => $plan->label])]), 200);
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
        $validated = $request->validate(['plan' => 'required|integer', 'user' => 'required|integer', 'starts_at' => 'nullable|date', 'expires_at' => 'nullable|date', 'trial_days' => 'nullable|integer|min:0', 'promotion_code' => 'nullable|string', 'force' => 'sometimes|boolean', 'mark_as_paid' => 'sometimes|boolean', 'payment_method' => 'nullable|integer', 'generate_invoice' => 'sometimes|boolean', 'reset_feature' => 'sometimes|boolean']);
        $validated['generate_invoice'] = $request->boolean('generate_invoice', true);
        $validated['reset_feature'] = $request->boolean('reset_feature', true);
        try {
            $user = Coderstm::$userModel::findOrFail($validated['user']);
        } catch (\Exception) {
            throw ValidationException::withMessages(['user' => __('The specified user does not exist.')]);
        }
        $service = app(SubscriptionService::class);
        $subscription = $service->createOrUpdate($user, $validated);

        return response()->json(['message' => __('Subscription has been created successfully.'), 'data' => $this->transformSubscription($subscription)], 201);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate(['plan' => 'required|integer', 'starts_at' => 'nullable|date', 'expires_at' => 'nullable|date', 'trial_days' => 'nullable|integer|min:0', 'promotion_code' => 'nullable|string', 'force' => 'sometimes|boolean', 'mark_as_paid' => 'sometimes|boolean', 'payment_method' => 'nullable|integer', 'generate_invoice' => 'sometimes|boolean', 'reset_feature' => 'sometimes|boolean']);
        $validated['generate_invoice'] = $request->boolean('generate_invoice', true);
        $validated['reset_feature'] = $request->boolean('reset_feature', true);
        try {
            $subscription = Coderstm::$subscriptionModel::findOrFail($id);
        } catch (\Exception) {
            throw ValidationException::withMessages(['id' => __('The specified subscription does not exist.')]);
        }
        $service = app(SubscriptionService::class);
        $subscription = $service->createOrUpdate($subscription->user, $validated, $subscription);

        return response()->json(['message' => __('Subscription has been updated successfully.'), 'data' => $this->transformSubscription($subscription)], 200);
    }

    public function show(Request $request, $id)
    {
        $subscription = $this->resolveSubscription($id);
        $this->assertUserSubscriptionAccess($subscription);
        $subscription->load(['plan']);
        $data = $this->transformSubscription($subscription);

        return response()->json($data, 200);
    }

    public function cancel(Request $request, $id)
    {
        $subscription = $this->resolveSubscription($id);
        if (guard('users')) {
            $this->assertUserSubscriptionAccess($subscription);
            if ($subscription->isContract()) {
                throw ValidationException::withMessages(['subscription' => __('Contract subscriptions cannot be cancelled until the end of their term. Please contact support for assistance.')]);
            }
        }
        if ($request->input('immediately', false)) {
            $subscription->cancelNow();
        } else {
            $subscription->cancel();
        }
        $subscription->cancelOpenInvoices();
        $user = $subscription->user;
        event(new SubscriptionCancel($subscription));
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
            event(new SubscriptionResume($subscription));

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
        try {
            $subscription->renew();

            return response()->json(['message' => __('Your subscription has been renewed successfully.'), 'subscription' => $subscription->fresh()->load(['plan', 'features'])], 200);
        } catch (\Throwable $e) {
            throw ValidationException::withMessages(['subscription' => $e->getMessage()]);
        }
    }

    public function freeze(Request $request, $id)
    {
        $request->validate(['release_at' => 'required|date|after:today', 'reason' => 'nullable|string|max:500']);
        $subscription = Subscription::findOrFail($id);
        $this->assertUserSubscriptionAccess($subscription);
        if (! $subscription->canFreeze()) {
            return response()->json(['message' => __('This subscription cannot be frozen.')], 422);
        }
        $freezeFee = $subscription->plan->freeze_fee ?? 0;
        $subscription->freeze(Carbon::parse($request->release_at), $request->reason, $freezeFee);

        return response()->json(['data' => $this->transformSubscription($subscription->fresh()), 'message' => __('Subscription has been frozen successfully!')], 200);
    }

    public function unfreeze(Request $request, $id)
    {
        $subscription = Subscription::findOrFail($id);
        $this->assertUserSubscriptionAccess($subscription);
        if (! $subscription->onFreeze()) {
            return response()->json(['message' => __('This subscription is not frozen.')], 422);
        }
        $subscription->unfreeze();

        return response()->json(['data' => $this->transformSubscription($subscription->fresh()), 'message' => __('Subscription has been unfrozen successfully!')], 200);
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
        if (guard('users') && $subscription->user_id != user('id')) {
            throw ValidationException::withMessages(['subscription' => __('You do not have access to this subscription.')]);
        }
    }

    protected function transformSubscription($subscription, array $extends = ['usages', 'plan', 'next_plan']): array
    {
        if (guard('admins')) {
            $extends[] = 'user';
        }

        return $subscription->toResponse($extends);
    }

    protected function user(Request $request)
    {
        if (guard('admins')) {
            return Coderstm::$userModel::find($request->input('user'));
        }

        return user();
    }
}
