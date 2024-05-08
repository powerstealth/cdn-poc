<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use MiladRahimi\Jwt\Validator\Rules\NotEmpty;
use MiladRahimi\Jwt\Validator\Rules\NotNull;
use Symfony\Component\HttpFoundation\Response;
use Modules\Auth\Presentation\Api\Resources\AuthResource;
use MiladRahimi\Jwt\Parser;
use MiladRahimi\Jwt\Cryptography\Keys\HmacKey;
use MiladRahimi\Jwt\Validator\Rules\NewerThan;
use MiladRahimi\Jwt\Validator\DefaultValidator;
use MiladRahimi\Jwt\Cryptography\Algorithms\Hmac\HS256;

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
