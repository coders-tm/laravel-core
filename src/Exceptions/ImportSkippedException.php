<?php

namespace Coderstm\Exceptions;

use Exception;

class ImportSkippedException extends Exception
{
    public function __construct($message = null)
    {
        parent::__construct($message ?? 'Already created or updated from CSV.');
    }
}
