<?php

namespace Coderstm\Http\Middleware;

use Closure;
use Coderstm\Coderstm;

class CheckSubscribed
{
    public function handle($request, Closure $next, $subscribed = false)
    {
        $user = $this->user();
        $subscription = $user->subscription();
        if ($user->subscribed()) {
            return $next($request);
        }

        return response()->json(['subscribed' => false, 'message' => __('To access exclusive features, please subscribe to a plan. You are not currently subscribed to any plan.')], 403);
    }

    private function user()
    {
        if (request()->filled('user_id') && is_admin()) {
            return Coderstm::$userModel::findOrFail(request()->user_id);
        }

        return user();
    }
}
