<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Modules\Auth\Presentation\Api\Resources\AuthResource;

class AuthSanctum
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try{
            $user=auth('sanctum')->user();
            if($user!==null)
                return $next($request);
            else
                throw new \Exception("Unauthorized");
        }catch (\Exception $e) {
            // Token could not be parsed
            $resource=AuthResource::from([
                "success"=>false,
                "message"=>"",
                "data"=>null,
                "error"=>$e->getMessage(),
                "response_status"=>401
            ]);
            return response()->json($resource,$resource->responseStatus);
        }
    }

}
