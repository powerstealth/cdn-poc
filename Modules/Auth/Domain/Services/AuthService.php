<?php

namespace Modules\Auth\Domain\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Modules\Auth\Domain\Repositories\AuthRepository;
use Modules\Auth\Domain\Traits\JwtTrait;

class AuthService
{
    use JwtTrait;

    protected AuthRepository $authRepository;

    /**
     * Constructor
     * @param AuthRepository $authRepository
     */
    public function __construct(AuthRepository $authRepository){
        $this->authRepository=$authRepository;
    }

    /**
     * SSO
     * @param string $jwtToken
     * @return array
     */
    public function sso(string $jwtToken):array{
        try {
            //get the claim
            $email=self::getClaims($jwtToken,['email','sub']);
            if(isset($email['email'])){
                //validate the email
                $emailIsValid=self::validateEmail($email['email']);
                if(!$emailIsValid) throw new \Exception("The email is invalid");
                $user=$this->authRepository->getUserByEmail($email['email']);
                if($user===null){
                    //create a new user
                    $sessionToken=$this->authRepository->createUser($email['email'],$email['sub']);
                    dd($sessionToken);
                }else{
                    //login user
                    dd($user->createToken('api_token')->plainTextToken);
                }
            }else{
                throw new \Exception("The email claim doesn't exists");
            }
        }catch (\Exception $e){
            return [
                "success"=>false,
                "message"=>"An error was occurred",
                "data"=>null,
                "error"=>$e->getMessage(),
                "response_status"=>401
            ];
        }



    }

}