<?php

namespace Modules\Auth\Presentation\Api\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Auth\Domain\Services\AuthService;
use App\Http\Controllers\Controller as Controller;
use Modules\Auth\Presentation\Api\Requests\AuthRequest;
use Modules\Auth\Presentation\Api\Requests\UserAdminRequest;
use Modules\Auth\Presentation\Api\Requests\UsersListRequest;
use Modules\Auth\Presentation\Api\Resources\AuthResource;

class AuthController extends Controller
{
    /**
     * @var AuthService
     */
    protected AuthService $authService;

    /**
     * Constructor
     * @param AuthService $authService
     */
    public function __construct(AuthService $authService)
    {
        $this->authService=$authService;
    }

    /**
     * Auth using SSO
     * @param AuthRequest $request
     * @return JsonResponse|null
     */
    public function sso(AuthRequest $request):null|JsonResponse
    {
        //get the thumbnail
        $response=$this->authService->sso($request->bearerToken());
        $resource=AuthResource::from($response);
        return response()->json($resource,$resource->responseStatus);
    }

    /**
     * Get user info using the session token
     * @param AuthRequest $request
     * @return JsonResponse|null
     */
    public function userInfo(AuthRequest $request):null|JsonResponse
    {
        $response=$this->authService->getUserInfo();
        $resource=AuthResource::from($response);
        return response()->json($resource,$resource->responseStatus);
    }

    /**
     * Set user Admin
     * @param UserAdminRequest $request
     * @return JsonResponse|null
     */
    public function setUserAdmin(UserAdminRequest $request):null|JsonResponse
    {
        //Validate the user
        $currentUserId = auth('sanctum')->user()->id;
        $passedUserId = $request->id;
        if($currentUserId==$passedUserId){
            $response = [
                "success"=>false,
                "message"=>"An error was occurred",
                "data"=>null,
                "error"=>"You are not authorized to update the user's grants",
                "response_status"=>403
            ];
        }else{
            $response=$this->authService->setUserAdmin($request->id, $request->data()->is_admin);
        }
        $resource=AuthResource::from($response);
        return response()->json($resource,$resource->responseStatus);
    }

    /**
     * Get users list
     * @param UsersListRequest $request
     * @return JsonResponse|null
     */
    public function getUsers(UsersListRequest $request):null|JsonResponse
    {
        $response=$this->authService->getUsers(
            $request->data()->page,
            $request->data()->limit,
            $request->data()->sortField,
            $request->data()->sortOrder,
            $request->data()->filters,
            $request->data()->search,
            $request->data()->setPagination
        );
        $resource=AuthResource::from($response);
        return response()->json($resource,$resource->responseStatus);
    }
}