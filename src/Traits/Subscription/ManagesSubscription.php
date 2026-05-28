<?php

namespace Coderstm\Traits\Subscription;

trait ManagesSubscription
{
    use ManagesSubscriptionCoupon;
    use ManagesSubscriptionFreeze;
    use ManagesSubscriptionPlan;
    use ManageSubscriptionInvoices;
    use ManageSubscriptionNotifications;
    use ManageSubscriptionStatus;
}
