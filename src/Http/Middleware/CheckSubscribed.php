<?php

namespace Coderstm\Http\Middleware;

use Closure;
use Coderstm\Coderstm;
use Illuminate\Http\Request;

class CheckSubscribed
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next, $subscribed = false)
    {
        $user = $this->user();
        $subscription = $user->subscription();

        // Allow active subscriptions (including those canceled but on grace period)
        if ($user->subscribed()) {
            return $next($request);
        }

        // Block unsubscribed users
        return response()->json([
            'subscribed' => false,
            'message' => __('To access exclusive features, please subscribe to a plan. You are not currently subscribed to any plan.'),
        ], 403);
    }

    private function user()
    {
        if (request()->filled('user_id') && is_admin()) {
            return Coderstm::$userModel::findOrFail(request()->user_id);
        }

        return user();
    }
}
