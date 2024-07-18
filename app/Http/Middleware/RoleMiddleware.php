<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next, ?string $role=""): Response
    {
        //Check the role
        if (! auth('sanctum')->user()->hasRole($role)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }
        return $next($request);
    }
}
