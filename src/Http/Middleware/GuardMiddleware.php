<?php

namespace Coderstm\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class GuardMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response|RedirectResponse)  $next
     * @return Response|RedirectResponse
     */
    public function handle(Request $request, Closure $next, ...$guards)
    {
        $guards = empty($guards) ? [null] : $guards;

        if (guard(...$guards)) {
            do_action('guard.after_resolved', $request, guard());

            return $next($request);
        }

        return $this->failed($guards);
    }

    protected function failed(array $guards)
    {
        if (request()->expectsJson()) {
            return response()->json([
                'message' => __('Unauthenticated.'),
            ], 401);
        }

        $guard = $guards[0] ?? null;

        $loginUrl = config("auth.guards.{$guard}.login", '/login');

        return redirect()->guest($loginUrl);
    }
}
