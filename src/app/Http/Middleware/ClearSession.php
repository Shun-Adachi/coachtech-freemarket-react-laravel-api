<?php

namespace App\Http\Middleware;

use Closure;

class ClearSession
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        session()->forget(['keyword']);
        session()->forget(['request_data']);
        return $next($request);
    }
}
