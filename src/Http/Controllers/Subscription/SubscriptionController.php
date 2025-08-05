<?php

namespace Coderstm\Http\Controllers\Subscription;

use Coderstm\Coderstm;
use Coderstm\Models\Coupon;
use Coderstm\Models\Redeem;
use Coderstm\Traits\Helpers;
use Illuminate\Http\Request;
use Coderstm\Models\PaymentMethod;
use Coderstm\Models\Subscription\Plan;
use Coderstm\Http\Controllers\Controller;
use Coderstm\Events\SubscriptionPlanChanged;
use Illuminate\Validation\ValidationException;
use Coderstm\Notifications\SubscriptionCancelNotification;
use Coderstm\Notifications\SubscriptionUpgradeNotification;
use Coderstm\Notifications\SubscriptionDowngradeNotification;
use Coderstm\Services\GatewaySubscriptionFactory;

class SubscriptionController extends Controller
{
    use Helpers;

    public function index(Request $request)
    {
        $user = $this->user();
        $subscription = $user->subscription();

        if ($user->onGenericTrial()) {
            $trial_end = $user->trial_ends_at->format('M d, Y');
            return response()->json([
                'message' => "You're on trial until $trial_end, <strong>but you haven't subscribed to any plan yet</strong>. Please do so now to contine using the application even after your trial ends.",
                'upcomingInvoice' => false,
            ], 200);
        } else if ($user->is_free_forever || !$subscription) {
            return response()->json([
                'message' => trans('messages.subscription.none'),
                'upcomingInvoice' => false,
            ], 200);
        }

        $upcomingInvoice = $subscription->upcomingInvoice();

        $subscription = $user->subscription()->load([
            'nextPlan',
            'planCanceled',
        ]);

        if ($subscription->canceled() && $subscription->onGracePeriod()) {
            if ($subscription->planCanceled) {
                $subscription['message'] = trans('messages.subscription.plan_canceled', [
                    'date' => $subscription->ends_at->format('d M, Y')
                ]);
            } else {
                $subscription['message'] = trans('messages.subscription.canceled', [
                    'date' => $subscription->ends_at->format('d M, Y')
                ]);
            }
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
            $subscription['upcomingInvoice'] =  [
                'amount' => $upcomingInvoice->total(),
                'date' => $upcomingInvoice->due_date->format('d M, Y'),
            ];

            if ($subscription->onTrial()) {
                $trial_end = $subscription->trial_ends_at->format('M d, Y');
                $subscription['message'] = "You're also on trial, until $trial_end. Once it ends, we'll charge you for your plan.";
            } else if ($subscription->hasDowngrade()) {
                $subscription['message'] = trans('messages.subscription.downgrade', [
                    'plan' => $subscription->nextPlan?->label,
                    'amount' => $upcomingInvoice->total(),
                    'date' => $subscription['upcomingInvoice']['date']
                ]);
            } else {
                $subscription['message'] = trans('messages.subscription.active', [
                    'amount' => $subscription['upcomingInvoice']['amount'],
                    'date' => $subscription['upcomingInvoice']['date']
                ]);
            }
        }

        $usages = $subscription->usagesToArray();

        $subscription->unsetRelation('nextPlan')
            ->unsetRelation('planCanceled')
            ->unsetRelation('owner')
            ->unsetRelation('usages');

        $subscription['usages'] = $usages;
        $subscription['canceled'] = $subscription->canceled();
        $subscription['ended'] = $subscription->ended();
        $subscription['is_valid'] = $subscription->valid() ?? false;

        return response()->json($subscription, 200);
    }

    public function subscribe(Request $request)
    {
        $request->validate([
            'plan' => 'required|exists:plans,id',
            'payment_method' => 'required_unless:admin,true|exists:payment_methods,id',
        ], [
            'payment_method.required_unless' => 'Please select a payment method to proceed with the subscription.',
        ]);

        $upgrade = $downgrade = false;
        $paymentMethod = PaymentMethod::find($request->payment_method);
        $provider = $paymentMethod?->provider;
        $subscription = null;
        $user = $this->user();
        $plan = Plan::find($request->plan);
        $subscribed = $user->subscribed();
        $trial_end = $user->trial_ends_at;
        $trial_days = $plan->trial_days;
        $coupon = optional(Coupon::findByCode($request->promotion_code));
        $oldPlan = null;

        // Check for existing subscription with same plan
        if ($subscribed && $user->subscription()->plan_id == $plan->id) {
            throw ValidationException::withMessages([
                'plan' => trans('validation.subscription.plan_already', ['plan' => $plan->label]),
            ]);
        }

        // Check for past due subscription
        if ($user->subscription() && $user->subscription()->pastDue()) {
            throw ValidationException::withMessages([
                'past_due' => 'Your subscription is past due. Please make a payment from the billing page or contact our reception for assistance.',
            ]);
        }

        try {
            if ($subscribed) {
                $subscription = $user->subscription();
                $downgrade = $plan->price < $subscription->plan->price;
                $needResume = $subscription->canceled() && $subscription->onGracePeriod() && !$subscription->plan->is_active;

                if ($downgrade || $needResume) {
                    $this->downgrade($subscription, [
                        'plan' => $plan->id,
                    ]);
                } else {
                    $oldPlan = $subscription->plan;
                    $subscription = $subscription->withCoupon($request->promotion_code)
                        ->swap($plan->id);

                    $upgrade = true;
                }
            } else {
                $subscription = $user->newSubscription('default', $plan->id)
                    ->withCoupon($request->promotion_code)
                    ->provider($provider);

                if ($trial_end && $trial_end->isFuture()) {
                    $subscription->trialUntil($trial_end);
                } else if ($trial_days && !$trial_end) {
                    $subscription->trialDays($trial_days);
                }

                $subscription->save();
            }
        } catch (\Exception $e) {
            throw $e;
        } finally {
            if ($coupon->id && $subscription) {
                Redeem::updateOrCreate([
                    'redeemable_type' => get_class($subscription),
                    'redeemable_id' => $subscription->id,
                    'coupon_id' => $coupon->id,
                ], [
                    'user_id' => $user->id,
                    'amount' => $coupon->getAmount($plan->price),
                ]);
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

    public function cancel(Request $request)
    {
        try {
            $user = $this->user();
            $subscription = $user->subscription();

            $subscription->cancel();

            event(new \Coderstm\Events\SubscriptionCancel($subscription));

            $user->notify(new SubscriptionCancelNotification($subscription));
        } catch (\Exception $e) {
            throw $e;
        }

        return response()->json([
            'message' => trans('messages.subscription.cancel')
        ], 200);
    }

    public function cancelDowngrade(Request $request)
    {
        $this->user()->subscription()->cancelDowngrade();
        return response()->json([
            'message' => trans('messages.subscription.upgraded')
        ], 200);
    }

    public function resume(Request $request)
    {
        $subscription = $this->user()->subscription()->resume();

        event(new \Coderstm\Events\SubscriptionResume($subscription));

        return response()->json([
            'message' => trans('messages.subscription.resume')
        ], 200);
    }

    public function pay(Request $request)
    {
        // Validate those rules
        $request->validate([
            'payment_method' => 'required|exists:payment_methods,id',
        ]);

        try {
            $user = $this->user();
            $subscription = $user->subscription();
            $subscription->pay($request->payment_method);
        } catch (\Exception $e) {
            throw $e;
        }

        return response()->json([
            'data' => $user->fresh(),
            'message' => trans('messages.subscription.due_payment')
        ], 200);
    }

    public function checkPromoCode(Request $request)
    {
        $request->validate([
            'promotion_code' => 'required|string',
            'plan_id' => 'required',
        ]);

        $planId = $request->input('plan_id');
        $couponCode = $request->input('promotion_code');

        // Retrieve the plan and coupon details from your database
        $coupon = Coupon::findByCode($couponCode);

        // Validate if the coupon exists
        if (!$coupon) {
            throw ValidationException::withMessages([
                'promotion_code' => ['Invalid coupon code'],
            ]);
        }

        // if the coupon is not active
        if (!$coupon->isActive()) {
            throw ValidationException::withMessages([
                'promotion_code' => ['Coupon is not active'],
            ]);
        }

        // if the coupon is expired
        if ($coupon->isExpired()) {
            throw ValidationException::withMessages([
                'promotion_code' => ['Coupon has expired'],
            ]);
        }

        if (!$coupon->canApplyToPlan($planId)) {
            throw ValidationException::withMessages([
                'promotion_code' => ['Invalid coupon code'],
            ]);
        }

        // Check if the coupon has reached its maximum redemptions
        if ($coupon->checkRaxRedemptions()) {
            throw ValidationException::withMessages([
                'promotion_code' => ['Coupon has reached maximum redemptions'],
            ]);
        }

        return response()->json($coupon->toPublic());
    }

    protected function user()
    {
        if (request()->filled('user_id') && is_admin()) {
            return Coderstm::$userModel::findOrFail(request()->user_id);
        }

        return user();
    }

    protected function downgrade($subscription, array $options = [])
    {

        if (!isset($options['plan']) || !$options['plan']) {
            throw ValidationException::withMessages([
                'plan' => trans('validation.subscriptions.downgrade_plan'),
            ]);
        }

        try {
            // Update the UserSubscription record with downgrade status
            $subscription->is_downgrade = true;
            $subscription->next_plan = $options['plan'];
            $subscription->save();

            $oldPlan = Plan::find($subscription->plan_id);
            $newPlan = Plan::find($options['plan']);

            $subscription->oldPlan = $oldPlan;
            $subscription->plan = $newPlan;
            $user = $this->user();

            // Update the subscription plan
            event(new SubscriptionPlanChanged($subscription, $oldPlan, $newPlan));

            $user->notify(new SubscriptionDowngradeNotification($subscription));
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
