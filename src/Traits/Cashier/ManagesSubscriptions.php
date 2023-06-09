<?php

namespace Coderstm\Traits\Cashier;

use Laravel\Cashier\Concerns\ManagesSubscriptions as CashierManagesSubscriptions;

trait ManagesSubscriptions
{
    use CashierManagesSubscriptions;

    /**
     * Get the subscribed status of the user.
     *
     * @return bool
     */
    public function getSubscribedAttribute()
    {
        return $this->subscribed() ?: false;
    }

    /**
     * Get the has cancelled status of the user.
     *
     * @return bool
     */
    public function getHasCancelledAttribute()
    {
        return $this->subscribed() ? $this->subscription()->canceled() : false;
    }
}
