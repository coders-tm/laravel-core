<?php

namespace Coderstm\Exceptions\Plan;

use Exception;

class FeatureNotFoundException extends Exception
{
    public function __construct()
    {
        parent::__construct('The requested feature was not found. Please contact support for further assistance.');
    }
}
