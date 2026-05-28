<?php

namespace Coderstm\Exceptions;

use Exception;

class IntegrityException extends Exception
{
    protected $code = 403;
}
