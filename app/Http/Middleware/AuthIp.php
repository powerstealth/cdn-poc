<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Modules\Auth\Presentation\Api\Resources\AuthResource;

class AuthIp
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try{
            $blockIps = ['127.0.0.1'];
            if (!in_array($request->ip(), $blockIps)) {
                throw new \Exception("Unauthorized");
            }else
                return $next($request);
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
