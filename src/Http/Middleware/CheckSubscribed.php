<?php

namespace Coderstm\Http\Middleware;

use Closure;
use Coderstm\Coderstm;

class CheckSubscribed
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, $subscribed = false)
    {
        $user = $this->user();
        $subscription = $user->subscription();
        if ($user->is_subscribed) {
            return $next($request);
        } else if ($subscription && $subscription->canceled()) {
            return response()->json([
                'cancelled' => true,
                'message' => trans('messages.subscription.canceled', [
                    'date' => $subscription->ends_at->format('d M, Y')
                ])
            ], 200);
        } else {
            return response()->json([
                'subscribed' => $subscribed,
                'message' => trans('messages.subscription.none'),
            ], 403);
        }
    }

    private function user()
    {
        if (request()->filled('user_id') && is_admin()) {
            return Coderstm::$userModel::findOrFail(request()->user_id);
        }
        return user();
    }
}
