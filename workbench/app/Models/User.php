<?php

namespace App\Models;

use Coderstm\Models\User as Base;
use Coderstm\Database\Factories\UserFactory;

class User extends Base
{
    public $taxRates = [];

    public $priceTaxRates = [];

    protected $guarded = [];

    /**
     * Get the address to sync with Stripe.
     *
     * @return array|null
     */
    public function stripeAddress()
    {
        return [
            'city' => 'Little Rock',
            'country' => 'US',
            'line1' => 'Main Str. 1',
            'line2' => 'Apartment 5',
            'postal_code' => '72201',
            'state' => 'Arkansas',
        ];
    }

    /**
     * Get the tax rates to apply to the subscription.
     *
     * @return array
     */
    public function taxRates()
    {
        return $this->taxRates;
    }

    /**
     * Get the tax rates to apply to individual subscription items.
     *
     * @return array
     */
    public function priceTaxRates()
    {
        return $this->priceTaxRates;
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return UserFactory::new();
    }
}
