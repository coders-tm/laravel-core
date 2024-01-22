<?php

namespace Coderstm\Traits\Cashier;

use Coderstm\Models\Cashier\PaymentMethod;
use Laravel\Cashier\Concerns\ManagesPaymentMethods as CashierManagesPaymentMethods;
use Laravel\Cashier\PaymentMethod as CashierPaymentMethod;

trait ManagesPaymentMethods
{
    use CashierManagesPaymentMethods;

    /**
     * Get all of the payment methods for the Stripe model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function payment_methods()
    {
        return $this->hasMany(PaymentMethod::class, $this->getForeignKey())
            ->orderBy('created_at', 'desc');
    }

    /**
     * Get default of the default payment methods for the Stripe model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function default_payment_method()
    {
        return $this->hasOne(PaymentMethod::class, $this->getForeignKey())
            ->orderBy('created_at', 'desc')
            ->where('is_default', 1);
    }

    /**
     * Converts a stripe payment method to an array of payment methods.
     *
     * @param \Laravel\Cashier\PaymentMethod $paymentMethod
     * @return array
     */
    protected function paymentMethodAsArray($paymentMethod)
    {
        return [
            'stripe_id' => $paymentMethod->id,
            'name' => $paymentMethod->billing_details->name,
            'card' =>  collect($paymentMethod->card)->only([
                'brand',
                'last4',
                'exp_month',
                'exp_year',
            ]),
            'brand' => $paymentMethod->card->brand,
            'last_four_digit' => $paymentMethod->card->last4,
            'card_number' => "XXXX XXXX XXXX {$paymentMethod->card->last4}",
            'exp_date' => "{$paymentMethod->card->exp_month}/{$paymentMethod->card->exp_year}",
        ];
    }

    /**
     * Add a payment method to the customer.
     *
     * @param  \Stripe\PaymentMethod|string  $paymentMethod
     * @return \Laravel\Cashier\PaymentMethod
     */
    public function addPaymentMethod($paymentMethod, $default = false)
    {
        if (!$this->hasStripeId()) {
            $this->createOrGetStripeCustomer();
        }

        $stripePaymentMethod = $this->resolveStripePaymentMethod($paymentMethod);

        if ($stripePaymentMethod->customer !== $this->stripe_id) {
            $stripePaymentMethod = $stripePaymentMethod->attach(
                ['customer' => $this->stripe_id]
            );
        }

        $cashierPaymentMethod = new CashierPaymentMethod($this, $stripePaymentMethod);

        $attributes = $this->paymentMethodAsArray($cashierPaymentMethod);

        $paymentMethod = $this->payment_methods()->updateOrCreate([
            'stripe_id' => $cashierPaymentMethod->id,
        ], $attributes);

        if ($default) {
            $paymentMethod->markAsDefault();
        }

        return $cashierPaymentMethod;
    }

    /**
     * Update customer's default payment method.
     *
     * @param  \Stripe\PaymentMethod|string  $paymentMethod
     * @return \Laravel\Cashier\PaymentMethod
     */
    public function updateDefaultPaymentMethod($paymentMethod)
    {
        $this->assertCustomerExists();

        $customer = $this->asStripeCustomer();

        $stripePaymentMethod = $this->resolveStripePaymentMethod($paymentMethod);

        // If the customer already has the payment method as their default, we can bail out
        // of the call now. We don't need to keep adding the same payment method to this
        // model's account every single time we go through this specific process call.
        if ($stripePaymentMethod->id === $customer->invoice_settings->default_payment_method) {
            return;
        }

        $cashierPaymentMethod = $this->addPaymentMethod($stripePaymentMethod);

        $this->updateStripeCustomer([
            'invoice_settings' => ['default_payment_method' => $cashierPaymentMethod->id],
        ]);

        // Next we will get the default payment method for this user so we can update the
        // payment method details on the record in the database. This will allow us to
        // show that information on the front-end when updating the payment methods.
        $this->fillPaymentMethodDetails($cashierPaymentMethod);

        $this->save();

        $paymentMethod = $this->findByPaymentMethod($cashierPaymentMethod->id);

        $paymentMethod->markAsDefault();

        return $cashierPaymentMethod;
    }

    /**
     * Delete a payment method from the customer.
     *
     * @param  \Stripe\PaymentMethod|string  $paymentMethod
     * @return void
     */
    public function deletePaymentMethod($paymentMethod)
    {
        $this->assertCustomerExists();

        $stripePaymentMethod = $this->resolveStripePaymentMethod($paymentMethod);

        if ($stripePaymentMethod->customer !== $this->stripe_id) {
            return;
        }

        $customer = $this->asStripeCustomer();

        $defaultPaymentMethod = $customer->invoice_settings->default_payment_method;

        $stripePaymentMethod->detach();

        // If the payment method was the default payment method, we'll remove it manually...
        if ($stripePaymentMethod->id === $defaultPaymentMethod) {
            $this->forceFill([
                'pm_type' => null,
                'pm_last_four' => null,
            ])->save();
        }

        $this->findByPaymentMethod($stripePaymentMethod->id)
            ->delete();
    }

    /**
     * Check the payment method for a given last 4 digits.
     *
     * @param string $last4 The last 4 digits of the payment method.
     * @return bool
     */
    public function checkPaymentMethod($last4 = '')
    {
        return $this->payment_methods()->where('last_four_digit', $last4)->count() > 0;
    }

    /**
     * Get the payment method with the specified last four digits.
     *
     * @param string $last4 The last four digits of the payment method.
     * @return mixed
     */
    public function getPaymentMethod($last4 = '')
    {
        return $this->payment_methods()->where('last_four_digit', $last4)->first();
    }

    protected function findByPaymentMethod($paymentMethod)
    {
        return $this->payment_methods()->where('stripe_id', $paymentMethod)->first();
    }
}
