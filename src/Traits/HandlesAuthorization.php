<?php

namespace Coderstm\Traits;

use Illuminate\Auth\Access\HandlesAuthorization as AccessHandlesAuthorization;
use Illuminate\Auth\Access\Response;

trait HandlesAuthorization
{
    use AccessHandlesAuthorization;

    protected function deny($message = null, $code = null)
    {
        return Response::deny($message ?? 'You do not have permission to access. Please contact your administrator to request access.', $code);
    }
}
