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
        try {
            if (! auth('sanctum')->user()->hasRole($role))
                throw new \Exception('Unauthorized');
            return $next($request);
        }catch (\Exception $exception){
            return response()->json(['error' => 'Forbidden'], 403);
        }
    }
}
