<?php

namespace Coderstm\Http\Controllers\Subscription;

use Coderstm\Coderstm;
use Stripe\Subscription;
use Coderstm\Models\Plan;
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
                'defaultPaymentMethod' => null
            ], 200);
        }

        $upcomingInvoice = $subscription->upcomingInvoice();
        $subscription['defaultPaymentMethod'] = $user->default_payment_method ?? null;

        if ($subscription->canceled() && $subscription->onGracePeriod()) {
            $subscription['message'] = trans('coderstm::messages.subscription.canceled', [
                'date' => $subscription->ends_at->format('d M, Y')
            ]);
        } else if ($subscription->pastDue() || $user->hasIncompletePayment()) {
            $invoice = $subscription->latestInvoice();
            $subscription['message'] = trans('coderstm::messages.subscription.past_due', [
                'amount' => $invoice->realTotal()
            ]);
        } else if ($upcomingInvoice) {
            $subscription['upcomingInvoice'] =  [
                'amount' => $upcomingInvoice->total(),
                'date' => $upcomingInvoice->date()->toFormattedDateString(),
            ];
            if ($subscription->is_downgrade) {
                $subscription['message'] = trans('coderstm::messages.subscription.downgrade', [
                    'date' => $subscription['upcomingInvoice']['date']
                ]);
            } else {
                $subscription['message'] = trans('coderstm::messages.subscription.active', [
                    'amount' => $subscription['upcomingInvoice']['amount'],
                    'date' => $subscription['upcomingInvoice']['date']
                ]);
            }
        }
        return response()->json($subscription, 200);
    }

    public function subscribe(Request $request)
    {
        $request->validate([
            'payment_method' => 'required|string',
            'plan' => 'required',
            'metadata' => 'array',
        ]);

        if ($request->input('payment_method') == 'manual') {
            $request->merge([
                'payment_method' => null
            ]);
        }

        $user = $this->user();
        $payment_method = $request->input('payment_method');
        $payment_interval = optional($request)->payment_interval ?? 'month';
        $plan = Plan::find($request->input('plan'));
        $price = Price::planById($request->input('plan'), $plan->is_custom ?? $payment_interval);
        $planID = $price->stripe_id;
        $isSubscribed = $user->subscribed();
        $subscription = null;
        $metadata = $request->input('metadata') ?? [];

        if ($isSubscribed && $user->subscription()->stripe_price == $planID) {
            throw ValidationException::withMessages([
                'plan' => trans('coderstm::validation.subscription.plan_already', ['plan' => $price->plan->label]),
            ]);
        }

        try {
            if ($isSubscribed) {
                $subscription = $user->subscription();
                if ($price->amount < $subscription->price->amount) {
                    $this->downgrade($subscription, [
                        'plan' => $planID,
                        'metadata' => $metadata,
                    ]);
                } else {
                    $subscription->releaseSchedule();
                    $subscription->swapAndInvoice($planID, [
                        'metadata' => $metadata,
                    ]);
                }
            } else {
                $subscription = $user->newSubscription('default', $planID)
                    ->withMetadata($metadata)
                    ->create($payment_method);
            }
        } catch (IncompletePayment $exception) {
            $paymentIntents = $this->paymentIntents($exception->payment->id);
            if ($paymentIntents['paymentIntent']['status'] != 'requires_payment_method') {
                return $paymentIntents;
            }
        }

        return response()->json([
            'subscription' => $subscription,
            'message' => trans_choice('coderstm::messages.subscription.success', $payment_method ? 0 : 1, ['plan' => $price->plan->label])
        ]);
    }

    public function confirm(Request $request)
    {
        $request->validate([
            'payment_intent' => 'required|string',
            'plan' => 'required|string',
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

        return response()->json([
            'subscription' => $user->subscription(),
            'message' => trans('coderstm::messages.subscription.success', ['plan' => $plan->label])
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

    public function invoices(Request $request)
    {
        return response()->json($this->user()->appInvoices()->orderByDesc('created_at')->paginate($request->rowsPerPage ?: 10), 200);
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
                'phases' => $updated_phases
            ]);


            // Update the UserSubscription record with downgrade status
            $subscription->is_downgrade = true;
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
