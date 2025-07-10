<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Auth\Domain\Traits\JwtTrait;
use Symfony\Component\HttpFoundation\Response;
use Modules\Auth\Presentation\Api\Resources\AuthResource;

class M2m
{
    use JwtTrait;

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $jwtClaims = self::validateJwt(request()->bearerToken(), true);

            // Error: parsing JWT
            if ($jwtClaims instanceof \Exception) {
                throw new \Exception("JWT parsing failed: " . $jwtClaims->getMessage());
            }

            // Error: JWT is missing 'sub' claim
            if (!isset($jwtClaims['sub'])) {
                throw new \Exception("JWT is missing 'sub' claim");
            }

            // Error: SUBS is wrong
            $allowedSubs = explode(',', env('SUBS'));
            if (!in_array($jwtClaims['sub'], $allowedSubs)) {
                throw new \Exception("Unauthorized 'sub' value: " . $jwtClaims['sub']);
            }

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
