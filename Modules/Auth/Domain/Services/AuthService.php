<?php

namespace Modules\Auth\Domain\Services;

use App\Models\User;
use Modules\Asset\Domain\Dto\PaginationDto;
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
            $issuer=self::getClaims($jwtToken,['iss']);
            if(isset($email['email'])){
                //validate the email
                $emailIsValid=self::validateEmail($email['email']);
                if(!$emailIsValid) throw new \Exception("The email is invalid");
                //validate the issuer
                if(env('JWT_ISSUER') != $issuer['iss'])
                    throw new \Exception("The issuer is invalid");
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
                throw new \Exception("The email doesn't exists");
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
     * Get Users List
     * @param int         $page
     * @param int         $limit
     * @param string      $sortField
     * @param string      $sortOrder
     * @param array       $filters
     * @param string|null $search
     * @param bool        $setPagination
     * @return array
     */
    public function getUsers(int $page, int $limit, string $sortField, string $sortOrder, array $filters, ?string $search, bool $setPagination):array{
        $data=$this->authRepository->listUsers($page,$limit,$sortField,$sortOrder,$filters,$search,$setPagination);
        return $this->_returnWithPagination($data,$setPagination);
    }

    /**
     * Set user Admin
     * @param string $userId
     * @param bool   $isAdmin
     * @return array
     */
    public function setUserAdmin(string $userId, bool $isAdmin):array
    {
        //deny the operation if the userId is the current user
        //set the role
        $adminRole='admin';
        //check if the role exists else create
        try {
            Role::findByName($adminRole,'api');
        }catch (\Exception $e){
            Role::create(['name' => 'admin']);
        }
        //find the user
        $user = $this->authRepository->getUserById($userId);
        if (!$user) {
            return [
                "success"=>false,
                "message"=>"The user doesn't exist",
                "data"=>null,
                "error"=>null,
                "response_status"=>404
            ];
        }else{
            //assign the role to user
            if($isAdmin)
                $user->syncRoles([$adminRole]);
            else
                $user->syncRoles([]);
            return [
                "success"=>true,
                "message"=>"",
                "data"=>null,
                "error"=>"",
                "response_status"=>200
            ];
        }
    }

    /**
     * Sign an Url
     * @param $request
     * @return array
     */
    public function signStreamingUrl($request):array
    {
        return [
            "success"=>true,
            "message"=>"",
            "data"=>self::signUrl($request),
            "error"=>"",
            "response_status"=>200
        ];
    }

    /**
     * Return
     * @param array|\Exception $data
     * @param bool             $setPagination
     * @return array
     */
    private function _returnWithPagination(array|\Exception $data, bool $setPagination):array
    {
        if($data instanceof \Exception){
            return [
                "success"=>false,
                "message"=>"An error was occurred",
                "data"=>null,
                "error"=>$data->getMessage(),
                "response_status"=>400
            ];
        }else{
            if($setPagination){
                $data=[
                    'items'=>$data["data"],
                    'pagination'=>new PaginationDto(
                        $data["current_page"],
                        $data["last_page"],
                        $data["total"],
                        $data["per_page"],
                        $data["next_page_url"]!=null ? true : false,
                        $data["prev_page_url"]!=null ? true : false,
                    )
                ];
            }
        }
        return [
            "success"=>true,
            "message"=>"",
            "data"=>$data,
            "error"=>null,
            "response_status"=>200
        ];
    }
}