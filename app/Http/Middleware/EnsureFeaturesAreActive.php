<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Laravel\Pennant\Feature;
use Laravel\Pennant\Middleware\EnsureFeaturesAreActive as BaseEnsureFeaturesAreActive;
use Symfony\Component\HttpFoundation\Response;

class EnsureFeaturesAreActive extends BaseEnsureFeaturesAreActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$features): Response
    {
        $name = "pennant:user:{$request->user()?->id}:features:".implode(',', $features);

        Cache::lock($name, 2)->block(3, function () use ($features) {
            return Feature::loadMissing($features);
        });

        return parent::handle($request, $next, ...$features);
    }
}
