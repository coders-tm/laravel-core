<?php

namespace Coderstm\Services\Admin;

use Coderstm\Models\Coupon;
use Coderstm\Models\PaymentMethod;
use Coderstm\Models\Redeem;
use Coderstm\Models\Subscription;
use Coderstm\Models\Subscription\Plan;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

class SubscriptionCreationService
{
    public function createOrUpdate($user, array $data, ?Subscription $subscription = null): Subscription
    {
        $this->validateCreateOrUpdateData($data, $subscription);
        $plan = $this->resolvePlan($data['plan']);
        $coupon = $this->resolveCoupon($data['promotion_code'] ?? null);
        $paymentMethod = $this->resolvePaymentMethod($data['payment_method'] ?? null, $data['mark_as_paid'] ?? false);
        try {
            if ($subscription) {
                $subscription = $this->updateExistingSubscription($subscription, $plan, $coupon, $data);
            } else {
                $subscription = $this->createNewSubscription($user, $plan, $coupon, $paymentMethod, $data);
            }
            if ($coupon && $subscription) {
                $this->applyRedemption($subscription, $coupon, $plan);
            }
            if ($data['mark_as_paid'] ?? false) {
                $this->markSubscriptionAsPaid($subscription, $paymentMethod);
            }

            return $subscription->fresh();
        } catch (\Throwable $e) {
            if ($e instanceof ValidationException) {
                throw $e;
            }
            throw ValidationException::withMessages(['subscription' => "Failed to create subscription: {$e->getMessage()}"]);
        }
    }

    protected function validateCreateOrUpdateData(array $data, ?Subscription $subscription = null): void
    {
        if (empty($data['plan'])) {
            throw ValidationException::withMessages(['plan' => __('A valid plan is required.')]);
        }
        if (! empty($data['starts_at']) && ! empty($data['expires_at'])) {
            $startsAt = \Carbon\Carbon::parse($data['starts_at']);
            $expiresAt = \Carbon\Carbon::parse($data['expires_at']);
            if ($expiresAt->lte($startsAt)) {
                throw ValidationException::withMessages(['expires_at' => __('Expiry date must be after the start date.')]);
            }
        }
        if ($data['mark_as_paid'] ?? false) {
            if (empty($data['payment_method'])) {
                throw ValidationException::withMessages(['payment_method' => __('Payment method is required when marking as paid.')]);
            }
        }
        if ($subscription && ! ($data['force'] ?? false)) {
            $newPlan = Plan::find($data['plan']);
            if ($subscription->plan_id === $newPlan->id) {
                throw ValidationException::withMessages(['plan' => __('User already subscribed to :plan plan.', ['plan' => $newPlan->label ?? $newPlan->id])]);
            }
        }
    }

    protected function createNewSubscription($user, Plan $plan, ?Coupon $coupon, ?PaymentMethod $paymentMethod, array $data): Subscription
    {
        $subscriptionBuilder = $user->newSubscription('default', $plan->id)->withCoupon($data['promotion_code'] ?? null);
        if ($paymentMethod) {
            $subscriptionBuilder->setProvider($paymentMethod->provider);
        }
        if (! empty($data['starts_at'])) {
            $subscriptionBuilder->setStartsAt($data['starts_at']);
        }
        if (! empty($data['expires_at'])) {
            $subscriptionBuilder->setExpiresAt($data['expires_at']);
        }
        if (! empty($data['trial_days'])) {
            $subscriptionBuilder->trialUntil($data['trial_days']);
        }

        return $subscriptionBuilder->saveAndInvoice();
    }

    protected function updateExistingSubscription(Subscription $subscription, Plan $plan, ?Coupon $coupon, array $data): Subscription
    {
        if (! empty($data['starts_at'])) {
            $subscription->setStartsAt($data['starts_at']);
        }
        if (! empty($data['expires_at'])) {
            $subscription->setExpiresAt($data['expires_at']);
        }
        if (! empty($data['trial_days'])) {
            $subscription->trialUntil($data['trial_days']);
        }
        if ($data['force'] ?? false) {
            $subscription = $subscription->withCoupon($data['promotion_code'] ?? null)->forceSwap($plan->id);
        } else {
            $subscription = $subscription->withCoupon($data['promotion_code'] ?? null)->swap($plan->id);
        }

        return $subscription;
    }

    protected function applyRedemption(Subscription $subscription, Coupon $coupon, Plan $plan): void
    {
        Redeem::updateOrCreate(['redeemable_type' => get_class($subscription), 'redeemable_id' => $subscription->id, 'coupon_id' => $coupon->id], ['user_id' => $subscription->user_id, 'amount' => $coupon->getAmount($plan->price)]);
    }

    protected function markSubscriptionAsPaid(Subscription $subscription, ?PaymentMethod $paymentMethod): void
    {
        if (! $paymentMethod) {
            throw ValidationException::withMessages(['payment_method' => __('Payment method is required to mark subscription as paid.')]);
        }
        try {
            $subscription->pay($paymentMethod->id);
        } catch (\Throwable $e) {
            throw ValidationException::withMessages(['payment' => __('Failed to process payment: :message', ['message' => $e->getMessage()])]);
        }
    }

    protected function resolvePlan($planId): Plan
    {
        try {
            return Plan::findOrFail($planId);
        } catch (ModelNotFoundException $e) {
            throw ValidationException::withMessages(['plan' => __('The selected plan is invalid.')]);
        }
    }

    protected function resolveCoupon(?string $couponCode): ?Coupon
    {
        if (empty($couponCode)) {
            return null;
        }
        $coupon = Coupon::findByCode($couponCode);
        if (! $coupon) {
            throw ValidationException::withMessages(['promotion_code' => __('The coupon code is invalid.')]);
        }

        return $coupon;
    }

    protected function resolvePaymentMethod($paymentMethodId, bool $required = false): ?PaymentMethod
    {
        if (empty($paymentMethodId) && ! $required) {
            return null;
        }
        if (empty($paymentMethodId) && $required) {
            throw ValidationException::withMessages(['payment_method' => __('Payment method is required.')]);
        }
        try {
            return PaymentMethod::findOrFail($paymentMethodId);
        } catch (ModelNotFoundException $e) {
            throw ValidationException::withMessages(['payment_method' => __('The selected payment method is invalid.')]);
        }
    }
}
