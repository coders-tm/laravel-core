<?php

namespace Coderstm\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontReport = [];

    protected $dontFlash = ['password', 'password_confirmation'];

    public function register() {}

    public function render($request, Throwable $e)
    {
        if ($e instanceof AuthorizationException) {
            $e = new AuthorizationException(__('You do not have permission to access. Please contact your administrator to request access.'), $e->getCode());
        }

        return parent::render($request, $e);
    }
}
