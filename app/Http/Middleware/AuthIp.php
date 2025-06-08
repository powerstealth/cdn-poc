<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Auth\Domain\Traits\JwtTrait;
use Symfony\Component\HttpFoundation\Response;
use Modules\Auth\Presentation\Api\Resources\AuthResource;

class AuthIp
{
    use JwtTrait;

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
                // Check if referrer domain (or full URL) is in allowed list
                (!empty($referer) && array_filter($allowedList, fn($item) => strpos(parse_url($referer, PHP_URL_HOST), $item) !== false)) ||

                // Check if IP is in allowed list
                (!empty($ip) && in_array($ip, $allowedList))
            ) {
                return $next($request);
            }else{
                if ($request->has('token')) {
                    if(self::checkSignedUrl($request->query('token')))
                        return $next($request);
                }
                throw new \Exception("Unauthorized");
            }
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
