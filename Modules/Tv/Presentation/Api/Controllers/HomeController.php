<?php

namespace Modules\Tv\Presentation\Api\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller as Controller;
use Modules\Tv\Domain\Services\HomeService;
use Modules\Tv\Presentation\Api\Requests\SetHomeRequest;
use Modules\Tv\Presentation\Api\Resources\HomeResource;

class HomeController extends Controller
{
    /**
     * @var HomeService
     */
    protected HomeService $homeService;

    /**
     * @param HomeService $homeService
     */
    public function __construct(HomeService $homeService)
    {
        $this->homeService=$homeService;
    }

    /**
     * Get home content list
     * @param Request $request
     * @return JsonResponse|null
     */
    public function getHomeContentList(Request $request):null|JsonResponse
    {
        $response=$this->homeService->getHomeContents(
            $request->section,
        );
        $resource=HomeResource::from($response);
        return response()->json($resource,$resource->responseStatus);
    }

    /**
     * Set the home contents
     * @param SetHomeRequest $request
     * @return JsonResponse|null
     */
    public function setHomeContentList(SetHomeRequest $request, string $section):null|JsonResponse
    {
        $response=$this->homeService->setHomeContents(
            $request->data()->items,
            $section
        );
        $resource=HomeResource::from($response);
        return response()->json($resource,$resource->responseStatus);
    }

}