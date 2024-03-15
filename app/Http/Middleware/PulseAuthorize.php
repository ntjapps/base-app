<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class PulseAuthorize
{
    /**
     * Create a new middleware instance.
     */
    public function __construct(protected Gate $gate)
    {
        //
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response|AuthorizationException
    {
        if (! App::environment('local')) {
            $this->gate->authorize('viewPulse');

            return $next($request);
        } else {
            return $next($request);
        }
    }
}
