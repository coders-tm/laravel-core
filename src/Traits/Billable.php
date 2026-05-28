<?php

namespace Coderstm\Traits;

use Coderstm\Traits\Subscription\ManagesCustomer;
use Coderstm\Traits\Subscription\ManagesInvoices;
use Coderstm\Traits\Subscription\ManagesSubscriptions;

trait Billable
{
    use ManagesCustomer;
    use ManagesInvoices;
    use ManagesSubscriptions;
}
