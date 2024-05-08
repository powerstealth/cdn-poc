<?php

namespace Modules\System\Presentation\Api\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\System\Domain\Services\SystemService;
use App\Http\Controllers\Controller as Controller;
use Modules\System\Presentation\Api\Requests\SystemRequest;
use Modules\System\Presentation\Api\Resources\SystemResource;

class SystemController extends Controller
{
    /**
     * @var SystemService
     */
    protected SystemService $systemService;

    /**
     * Constructor
     * @param SystemService $systemService
     */
    public function __construct(SystemService $systemService)
    {
        $this->systemService=$systemService;
    }

    /**
     * Ping
     * @param Request $request
     * @return JsonResponse|null
     */
    public function ping(Request $request):null|JsonResponse
    {
        //get the thumbnail
        $response=$this->systemService->ping();
        $resource=SystemResource::from($response);
        return response()->json($resource,$resource->responseStatus);
    }

}