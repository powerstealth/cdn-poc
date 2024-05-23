<?php

namespace Modules\Asset\Presentation\Api\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Asset\Domain\Dto\InfoDto;
use Modules\Asset\Domain\Services\AssetService;
use App\Http\Controllers\Controller as Controller;
use Modules\Asset\Presentation\Api\Requests\AssetInfoRequest;
use Modules\Asset\Presentation\Api\Requests\AssetListRequest;
use Modules\Asset\Presentation\Api\Requests\AssetUpdateRequest;
use Modules\Asset\Presentation\Api\Resources\AssetResource;
use Modules\Asset\Presentation\Api\Requests\AssetMultipartUploadRequest;
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
    public function setUploadSession(AssetUploadSessionRequest $request):null|JsonResponse
    {
        $response=$this->assetService->setUploadSession(
            $request->data()->file_name,
            $request->data()->file_length,
            $request->data()->scope_clyup_tv,
            $request->data()->scope_clyup_front_store
        );
        $resource=AssetResource::from($response);
        return response()->json($resource,$resource->responseStatus);
    }

    /**
     * Multipart upload
     * @param AssetMultipartUploadRequest $request
     * @return JsonResponse|null
     */
    public function multipartUpload(AssetMultipartUploadRequest $request):null|JsonResponse
    {
        $response=$this->assetService->setMultipartUpload(
            $request->data()->task,
            $request->data()->file_name,
            $request->data()->file_length,
            $request->data()->scope_clyup_tv,
            $request->data()->scope_clyup_front_store,
            $request->data()->asset_id,
            $request->data()->parts,
        );
        $resource=AssetResource::from($response);
        return response()->json($resource,$resource->responseStatus);
    }

    /**
     * Get assets list
     * @param AssetListRequest $request
     * @return JsonResponse|null
     */
    public function getAssets(AssetListRequest $request):null|JsonResponse
    {
        $response=$this->assetService->getAssets(
            $request->data()->page,
            $request->data()->limit,
            $request->data()->sortField,
            $request->data()->sortOrder,
            $request->data()->filters,
            $request->data()->setPagination
        );
        $resource=AssetResource::from($response);
        return response()->json($resource,$resource->responseStatus);
    }

    /**
     * Get asset
     * @param AssetInfoRequest $request
     * @return JsonResponse|null
     */
    public function getAsset(AssetInfoRequest $request):null|JsonResponse
    {
        $response=$this->assetService->getAsset($request->data()->id);
        $resource=AssetResource::from($response);
        return response()->json($resource,$resource->responseStatus);
    }

    /**
     * Update asset
     * @param AssetUpdateRequest $request
     * @return JsonResponse|null
     */
    public function updateAsset(AssetUpdateRequest $request):null|JsonResponse
    {
        $response=$this->assetService->updateAsset($request->data()->id, $request->data()->toArray());
        $resource=AssetResource::from($response);
        return response()->json($resource,$resource->responseStatus);
    }

    /**
     * Delete an asset
     * @param AssetInfoRequest $request
     * @return JsonResponse|null
     */
    public function deleteAsset(AssetInfoRequest $request):null|JsonResponse
    {
        $response=$this->assetService->deleteAsset($request->data()->id, false);
        $resource=AssetResource::from($response);
        return response()->json($resource,$resource->responseStatus);
    }
}