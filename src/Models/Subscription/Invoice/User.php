<?php

namespace Coderstm\Models\Subscription\Invoice;

use Coderstm\Models\User as Base;

class User extends Base
{
    protected $appends = [
        'name',
    ];

    protected $with = [
        //
    ];
}
