<?php

namespace Workbench\App\Models;

use Coderstm\Database\Factories\AdminFactory;
use Coderstm\Models\Admin as Base;

class Admin extends Base
{
    protected $guarded = [];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return AdminFactory::new();
    }
}
