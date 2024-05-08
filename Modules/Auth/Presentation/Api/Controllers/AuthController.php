<?php

namespace Modules\Auth\Presentation\Api\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Auth\Domain\Services\AuthService;
use App\Http\Controllers\Controller as Controller;
use Modules\Auth\Presentation\Api\Requests\AuthRequest;
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

}