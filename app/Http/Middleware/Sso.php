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

class Sso
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try{
            $checkJwtToken=$this->_validateJwtToken($request->bearerToken());
            if($checkJwtToken===true)
                return $next($request);
            else
                throw new \Exception($checkJwtToken->getMessage());
        }catch (\Exception $e) {
            // Token could not be parsed
            $resource=AuthResource::from([
                "success"=>false,
                "message"=>"The token is invalid",
                "data"=>null,
                "error"=>$e->getMessage(),
                "response_status"=>401
            ]);
            return response()->json($resource,$resource->responseStatus);
        }
    }

    /**
     * Validate JWT token
     * @param string $jwt
     * @return bool|\Exception
     */
    private function _validateJwtToken(string $jwt):bool|\Exception {
        $key = new HmacKey(env("JWT_SIGNING_KEY"));
        $signer = new HS256($key);
        $validator = new DefaultValidator();
        //validate the issuer
        $validator->addRequiredRule('iss', new NotEmpty());
        $validator->addRequiredRule('iss', new NotNull());
        //validate the subject
        $validator->addRequiredRule('sub', new NotEmpty());
        $validator->addRequiredRule('sub', new NotNull());
        //validate expiration
        $validator->addOptionalRule('exp', new NewerThan(time()));
        $validator->addRequiredRule('exp', new NotEmpty());
        $validator->addRequiredRule('exp', new NotNull());
        //validate email
        $validator->addRequiredRule('email', new NotEmpty());
        $validator->addRequiredRule('email', new NotNull());
        try {
            $parser = new Parser($signer,$validator);
            $parser->parse($jwt);
        }catch (\Exception $e){
            return $e;
        }
        return true;
    }
}
