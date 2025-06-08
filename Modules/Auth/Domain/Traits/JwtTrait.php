<?php
namespace Modules\Auth\Domain\Traits;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use MiladRahimi\Jwt\Generator;
use MiladRahimi\Jwt\Parser;
use MiladRahimi\Jwt\Cryptography\Algorithms\Hmac\HS256;
use MiladRahimi\Jwt\Cryptography\Keys\HmacKey;
use MiladRahimi\Jwt\Validator\DefaultValidator;
use MiladRahimi\Jwt\Validator\Rules\NotEmpty;
use MiladRahimi\Jwt\Validator\Rules\NotNull;

trait JwtTrait{

    /**
     * Get the claims
     * @param string     $jwt
     * @param array|null $claims
     * @return array|\Exception
     */
    public static function getClaims(string $jwt,?array $claims):array|\Exception{
        $key = new HmacKey(env("JWT_SIGNING_KEY"));
        $signer = new HS256($key);
        try {
            $parser = new Parser($signer);
            $parsedClaims=$parser->parse($jwt);
            if($claims==null){
                return $claims;
            }else{
                $newClaims=[];
                foreach ($claims as $claim)
                    if(isset($parsedClaims[$claim]))
                        $newClaims[$claim]=$parsedClaims[$claim];
                return $newClaims;
            }
        }catch (\Exception $e){
            return $e;
        }
    }

    /**
     * Validate an email
     * @param string $email
     * @return bool
     */
    public static function validateEmail(string $email):bool{
        // Regular expression for email validation
        $pattern = '/^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})+$/';
        // Check if the email matches the pattern
        if (preg_match($pattern, $email)) {
            return true; // Valid email
        } else {
            return false; // Invalid email
        }
    }

    /**
     * Sign Url
     * @param string $url
     * @return string|null
     */
    public static function signUrl(string $url):?string{
        $key = new HmacKey(env("JWT_SIGNING_KEY"));
        $signer = new HS256($key);
        try {
            $generator = new Generator($signer);

            $now = time();
            $exp = $now + (30 * 60); // 30 minutes from now

            $payload = [
                'url' => $url,
                'iat' => $now,
                'exp' => $exp,
            ];

            return $generator->generate($payload);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Check Signed Url
     * @param string $token
     * @return bool
     */
    public static function checkSignedUrl(string $token):bool{
        $key = new HmacKey(env("JWT_SIGNING_KEY"));
        $signer = new HS256($key);
        try {
            $parser = new Parser($signer);
            $claims=$parser->parse($token);
            return true;
        }catch (\Exception $e){
            return false;
        }
    }
}