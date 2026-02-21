<?php

namespace Coderstm\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PreserveJsonWhitespace
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->isJson() && $request->has('data')) {
            $rawContent = $request->getContent();
            if (! empty($rawContent)) {
                try {
                    $rawData = json_decode($rawContent, true, 512, JSON_THROW_ON_ERROR);
                    if (isset($rawData['data'])) {
                        $request->merge(['data' => $rawData['data']]);
                    }
                } catch (\JsonException $e) {
                }
            }
        }

        return $next($request);
    }
}
