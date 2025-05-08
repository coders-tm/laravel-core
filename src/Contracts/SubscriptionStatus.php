<?php

namespace Coderstm\Contracts;

interface SubscriptionStatus
{
    const ACTIVE = 'active';
    const CANCELED = 'canceled';
    const INCOMPLETE = 'incomplete';
    const INCOMPLETE_EXPIRED = 'incomplete_expired';
    const PAST_DUE = 'past_due';
    const PAUSED = 'paused';
    const TRIALING = 'trialing';
    const UNPAID = 'unpaid';
    const PENDING = 'pending';
}
