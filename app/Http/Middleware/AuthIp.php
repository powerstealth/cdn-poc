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
            $blockReferrals = env("REFERRALS", '');
            if (empty($blockReferrals)) {
                return $next($request);
            }
            $referer = $request->header('referer');
            $ip = $request->ip();
            $allowedList = array_map('trim', explode(',', $blockReferrals));
            if (
                (!empty($referer) && in_array($referer, $allowedList)) ||
                (!empty($ip) && in_array($ip, $allowedList))
            ) {
                return $next($request);
            }
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
