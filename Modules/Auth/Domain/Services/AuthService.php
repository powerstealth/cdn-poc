<?php

namespace Modules\Auth\Domain\Services;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Traits\HasRoles;
use Modules\Auth\Domain\Traits\JwtTrait;
use Modules\Auth\Domain\Repositories\AuthRepository;

class AuthService
{
    use JwtTrait, HasRoles;

    protected AuthRepository $authRepository;

    /**
     * Constructor
     * @param AuthRepository $authRepository
     */
    public function __construct(AuthRepository $authRepository){
        $this->authRepository=$authRepository;
    }

    /**
     * Check SSO
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
                }else{
                    //login user
                    $sessionToken=$user->createToken('api_token')->plainTextToken;
                }
                return [
                    "success"=>true,
                    "message"=>"",
                    "data"=>[
                        "session_token"=>$sessionToken
                    ],
                    "error"=>"",
                    "response_status"=>200
                ];
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

    /**
     * Get user info
     * @return array
     */
    public function getUserInfo():array{
        try {
            //get user info
            $loggedUser = auth('sanctum')->user();
            $userInfo = $this->authRepository->getUserById($loggedUser->_id);
            return [
                "success"=>true,
                "message"=>"",
                "data"=>$userInfo->toArray(),
                "error"=>"",
                "response_status"=>200
            ];
        }catch (\Exception $e){
            return [
                "success"=>false,
                "message"=>"Forbidden",
                "data"=>null,
                "error"=>$e->getMessage(),
                "response_status"=>401
            ];
        }
    }

    /**
     * @param string $newRole
     * @param string $userId
     * @return bool
     */
    public function setUserRole(string $newRole, string $userId):bool
    {
        //check if the role exists else create
        try {
            $role = Role::findByName($newRole,'api');
        }catch (\Exception $e){
            $role = Role::create(['name' => 'admin']);
        }
        //find the user
        $user = $this->authRepository->getUserById($userId);
        if (!$user) {
            return false;
        }else{
            //assign the role to user
            $user->syncRoles([$newRole]);
            return true;
        }
    }
}