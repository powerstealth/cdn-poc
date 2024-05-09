<?php

namespace Modules\Asset\Presentation\Api\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Asset\Domain\Services\AssetService;
use App\Http\Controllers\Controller as Controller;
use Modules\Asset\Presentation\Api\Resources\AssetResource;
use Modules\Asset\Presentation\Api\Requests\AssetUploadSessionRequest;

class AssetController extends Controller
{
    /**
     * @var AssetService
     */
    protected AssetService $assetService;

    /**
     * Constructor
     * @param AssetService $assetService
     */
    public function __construct(AssetService $assetService)
    {
        $this->assetService=$assetService;
    }

    /**
     * Get an upload session
     * @param AssetUploadSessionRequest $request
     * @return JsonResponse|null
     */
    public function getUploadSession(AssetUploadSessionRequest $request):null|JsonResponse
    {
        $response=$this->assetService->getUploadSession(
            $request->data()->file_length,
            $request->data()->file_type,
            $request->data()->scope_clyup_tv,
            $request->data()->scope_clyup_front_store
        );
        $resource=AssetResource::from($response);
        return response()->json($resource,$resource->responseStatus);
    }
}