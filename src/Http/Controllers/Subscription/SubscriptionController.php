<?php

namespace Coderstm\Http\Controllers\Subscription;

use Coderstm\Coderstm;
use Stripe\Subscription;
use Coderstm\Models\Plan;
use Coderstm\Models\Coupon;
use Coderstm\Models\Redeem;
use Illuminate\Support\Arr;
use Coderstm\Traits\Helpers;
use Illuminate\Http\Request;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Payment;
use Coderstm\Models\Plan\Price;
use Coderstm\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;
use Laravel\Cashier\Exceptions\IncompletePayment;
use Coderstm\Notifications\SubscriptionCancelNotification;
use Coderstm\Notifications\SubscriptionUpgradeNotification;
use Coderstm\Notifications\SubscriptionDowngradeNotification;

class SubscriptionController extends Controller
{
    use Helpers;

    public function index(Request $request)
    {
        $user = $this->user();

        $subscription = $user->subscription();

        if ($user->is_free_forever || !$subscription) {
            return response()->json([
                'message' => trans('coderstm::messages.subscription.none'),
                'upcomingInvoice' => false,
            ], 200);
        }

        $subscription = $user->subscription()->load([
            'nextPrice',
            'planCanceled',
        ]);

        $upcomingInvoice = $subscription->upcomingInvoice();

        if ($subscription->canceled() && $subscription->onGracePeriod() && !$subscription->hasSchedule()) {
            if ($subscription->planCanceled) {
                $subscription['message'] = trans('coderstm::messages.subscription.plan_canceled', [
                    'date' => $subscription->ends_at->format('d M, Y')
                ]);
            } else {
                $subscription['message'] = trans('coderstm::messages.subscription.canceled', [
                    'date' => $subscription->ends_at->format('d M, Y')
                ]);
            }
        } else if ($subscription->pastDue() || $subscription->hasIncompletePayment()) {
            $invoice = $subscription->latestInvoice();
            $amount = $invoice->realTotal();
            $subscription['message'] = trans('coderstm::messages.subscription.past_due', [
                'amount' => $amount
            ]);
            $subscription['dueAmount'] = $amount;
            $subscription['hasDue'] = true;
        } else if ($upcomingInvoice) {
            $subscription['upcomingInvoice'] =  [
                'amount' => $upcomingInvoice->total(),
                'date' => $upcomingInvoice->date()->toFormattedDateString(),
            ];
            if ($subscription->hasSchedule()) {
                $subscription['message'] = trans('coderstm::messages.subscription.downgrade', [
                    'plan' => $subscription->nextPrice->label,
                    'amount' => $upcomingInvoice->total(),
                    'date' => $subscription['upcomingInvoice']['date']
                ]);
            } else {
                $subscription['message'] = trans('coderstm::messages.subscription.active', [
                    'amount' => $subscription['upcomingInvoice']['amount'],
                    'date' => $subscription['upcomingInvoice']['date']
                ]);
            }
        }

        $subscription->unsetRelation('nextPrice');
        $subscription->unsetRelation('planCanceled');

        return response()->json($subscription, 200);
    }

    public function subscribe(Request $request)
    {
        $request->validate([
            'payment_method' => 'required|string',
            'plan' => 'required|exists:plan_prices,id',
            'metadata' => 'array',
        ]);

        if ($request->input('payment_method') == 'manual') {
            $request->merge([
                'payment_method' => null
            ]);
        }

        $user = $this->user();
        $payment_method = $request->input('payment_method');
        $price = Price::find($request->plan);
        $stripe_price = $price->stripe_id;
        $subscribed = $user->subscribed();
        $subscription = null;
        $metadata = $request->input('metadata') ?? [];
        $upgrade = false;
        $coupon = optional(Coupon::findByCode($request->promotion_code));

        if ($subscribed && $user->subscription()->stripe_price == $stripe_price) {
            throw ValidationException::withMessages([
                'plan' => trans('coderstm::validation.subscription.plan_already', ['plan' => $price->label]),
            ]);
        }

        if ($user->subscription() && $user->subscription()->pastDue()) {
            throw ValidationException::withMessages([
                'past_due' => 'Your subscription is past due. Please make a payment from the billing page or contact our reception for assistance.',
            ]);
        }

        try {
            if ($subscribed) {
                $subscription = $user->subscription();
                $downgrade = $price->amount < $subscription->price->amount;
                $needResume = $subscription->canceled() && $subscription->onGracePeriod() && !$subscription->price->is_active;

                if ($downgrade || $needResume) {
                    $this->downgrade($subscription, [
                        'plan' => $stripe_price,
                        'payment_method' => $payment_method,
                        'metadata' => $metadata,
                    ]);
                } else {
                    if (!$payment_method && is_user()) {
                        throw ValidationException::withMessages([
                            'payment_method' => trans_choice('coderstm::messages.subscription.success', 1, ['plan' => $price->label]),
                        ]);
                    }

                    $subscription->releaseSchedule();

                    $user->updateStripeCustomer([
                        'invoice_settings' => ['default_payment_method' => $payment_method],
                    ]);

                    $subscription->withCoupon($coupon->stripe_id)
                        ->swapAndInvoice($stripe_price, array_merge([
                            'metadata' => $metadata,
                            'default_payment_method' => $payment_method,
                        ]));

                    $upgrade = true;
                }
            } else {
                $subscription = $user->newSubscription('default', $stripe_price)
                    ->withMetadata($metadata)
                    ->withCoupon($coupon->stripe_id)
                    ->create($payment_method);
            }
        } catch (IncompletePayment $exception) {
            $upgrade = false;
            $paymentIntents = $this->paymentIntents($exception->payment->id);
            if ($paymentIntents['paymentIntent']['status'] != 'requires_payment_method') {
                return $paymentIntents;
            }
        } finally {
            if ($coupon->id && $subscription) {
                Redeem::updateOrCreate([
                    'redeemable_type' => get_class($subscription),
                    'redeemable_id' => $subscription->id,
                    'coupon_id' => $coupon->id,
                ]);
            }

            if ($upgrade) {
                $subscription->syncOrResetUsages();
                $subscription->oldPlan = $subscription->price;
                $subscription->price = $price;
                $user->notify(new SubscriptionUpgradeNotification($user, $subscription));
            }
        }

        return response()->json([
            'subscription' => $subscription,
            'message' => trans_choice('coderstm::messages.subscription.success', $payment_method ? 0 : 1, ['plan' => $price->label])
        ]);
    }

    public function confirm(Request $request)
    {
        $request->validate([
            'payment_intent' => 'required|string',
        ]);

        $user = $this->user();
        $payment_intent = $request->input('payment_intent');
        $plan = Plan::find($request->plan);

        try {
            $payment = new Payment(
                Cashier::stripe()->paymentIntents->retrieve(
                    $payment_intent,
                    ['expand' => ['payment_method']]
                )
            );
            if ($payment->isSucceeded()) {
                // confirm the subscription
                $subscription = $user->subscription();
                $subscription->stripe_status = Subscription::STATUS_ACTIVE;
                $subscription->save();
            } else {
                abort(403, trans('coderstm::messages.payment_method.authenticate'));
            }
        } catch (\Exception $e) {
            throw $e;
        }

        if ($plan) {
            $message = trans_choice('coderstm::messages.subscription.success', 0, [
                'plan' => $plan->label
            ]);
        } else {
            $message = 'Your payment has been confirmed successfully!';
        }

        return response()->json([
            'subscription' => $user->subscription(),
            'message' => $message
        ]);
    }

    public function cancel(Request $request)
    {
        try {
            $user = $this->user();
            $subscription = $user->subscription();

            $subscription->cancel();

            $user->notify(new SubscriptionCancelNotification($user, $subscription));
        } catch (\Exception $e) {
            throw $e;
        }

        return response()->json([
            'message' => trans('coderstm::messages.subscription.cancel')
        ], 200);
    }

    public function cancelDowngrade(Request $request)
    {
        $this->user()->subscription()->releaseSchedule();
        return response()->json([
            'message' => trans('coderstm::messages.subscription.upgraded')
        ], 200);
    }

    public function resume(Request $request)
    {
        $this->user()->subscription()->resume();

        return response()->json([
            'message' => trans('coderstm::messages.subscription.resume')
        ], 200);
    }

    public function pay(Request $request)
    {
        if ($request->input('payment_method') == 'manual') {
            $request->merge([
                'payment_method' => null
            ]);
        }

        $request->validate([
            'payment_method' => 'required|string',
        ]);

        try {
            $user = $this->user();
            $subscription = $user->subscription();
            $subscription->pay($request->payment_method);
        } catch (IncompletePayment $exception) {
            $paymentIntents = $this->paymentIntents($exception->payment->id);
            if ($paymentIntents['paymentIntent']['status'] != 'requires_payment_method') {
                return $paymentIntents;
            }
        } catch (\Exception $e) {
            throw $e;
        }

        return response()->json([
            'data' => $user->fresh(),
            'message' => trans('coderstm::messages.subscription.due_payment')
        ], 200);
    }

    public function invoices(Request $request)
    {
        $invoices = $this->user()
            ->appInvoices()
            ->paid()
            ->orderByDesc('created_at')
            ->paginate($request->rowsPerPage ?: 10);
        return response()->json($invoices, 200);
    }

    public function downloadInvoice(Request $request, $invoiceId)
    {
        $user = $this->user();
        return $user->downloadInvoice($invoiceId, [
            'vendor' => config('app.name'),
            // 'product' => Str::title("{$user->plan->label} plan"),
        ], 'my-invoice');
    }

    /**
     * Creates an intent for payment so we can capture the payment
     * method for the user.
     *
     * @param Request $request The request data from the user.
     */
    public function getSetupIntent(Request $request)
    {
        return $this->user()->createSetupIntent();
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

    protected function paymentIntents($id)
    {
        $payment = new Payment(
            Cashier::stripe()->paymentIntents->retrieve(
                $id,
                ['expand' => ['payment_method']]
            )
        );

        $paymentIntent = Arr::only($payment->asStripePaymentIntent()->toArray(), [
            'id', 'status', 'payment_method_types', 'client_secret', 'payment_method',
        ]);

        $paymentIntent['payment_method'] = Arr::only($paymentIntent['payment_method'] ?? [], 'id');

        return [
            'amount' => $payment->amount(),
            'payment' => $payment,
            'paymentIntent' => array_filter($paymentIntent),
            'paymentMethod' => (string) optional($payment->payment_method)->type,
            'customer' => $payment->customer(),
            'requiresAction' => true,
        ];
    }

    protected function user()
    {
        if (request()->filled('user_id') && is_admin()) {
            return Coderstm::$userModel::findOrFail(request()->user_id);
        }
        return current_user();
    }

    protected function downgrade($subscription, array $options = [])
    {

        if (!isset($options['plan'])) {
            throw ValidationException::withMessages([
                'plan' => trans('coderstm::validation.subscriptions.downgrade_plan'),
            ]);
        }

        try {
            $stripeSubscription = $subscription->asStripeSubscription();

            if ($stripeSubscription->schedule) {
                $subscriptionSchedule = Cashier::stripe()->subscriptionSchedules->retrieve($stripeSubscription->schedule);
            } else {
                $subscriptionSchedule = Cashier::stripe()->subscriptionSchedules->create([
                    'from_subscription' => $subscription->stripe_id,
                ]);
            }

            // Get the current phase (the first phase in the phases array)
            $currPhase = $subscriptionSchedule->phases[0];

            // Create the updated phases array for the subscription schedule
            $updated_phases = [
                [
                    'items' => [
                        [
                            'price' => $currPhase->items[0]->price,
                            'quantity' => 1,
                        ],
                    ],
                    'start_date' => $currPhase->start_date,
                    'end_date' => $currPhase->end_date,
                    'proration_behavior' => 'none',
                ],
                [
                    'items' => [
                        [
                            'price' => $options['plan'],
                            'quantity' => 1,
                        ],
                    ],
                    'metadata' => $options['metadata'] ?? [],
                    'proration_behavior' => 'none',
                    'iterations' => 1,
                ],
            ];

            Cashier::stripe()->subscriptionSchedules->update($subscriptionSchedule->id, [
                'phases' => $updated_phases,
            ]);


            // Update the UserSubscription record with downgrade status
            $subscription->is_downgrade = true;
            $subscription->next_plan = $options['plan'];
            $subscription->schedule = $subscriptionSchedule->id; // or the date of the next renewal with the downgrade
            $subscription->save();

            $subscription->oldPlan = Price::findByStripeId($currPhase->items[0]->price);
            $subscription->price = Price::findByStripeId($options['plan']);
            $user = $this->user();
            $user->notify(new SubscriptionDowngradeNotification($user, $subscription));
        } catch (\Stripe\Exception\ApiErrorException $e) {
            throw $e;
        }
    }
}
