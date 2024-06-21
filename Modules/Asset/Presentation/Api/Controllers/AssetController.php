<?php

namespace Modules\Asset\Presentation\Api\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
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
            $request->data()->asset_id,
            $request->data()->parts,
            $request->data()->data,
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
            $request->data()->search,
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
        $response=$this->assetService->updateAsset($request->data()->id, $request->data()->toArray(), $request->data()->published);
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

    /**
     * Redirect the user to stream an asset
     * @param AssetInfoRequest $request
     * @return RedirectResponse|JsonResponse
     */
    public function streamAsset(AssetInfoRequest $request):RedirectResponse|JsonResponse
    {
        //check availability
        $response=$this->assetService->canStreamAsset($request->data()->id, $request->data()->json);
        //response
        if($response===false){
            return response()->json([],401);
        }else{
            if(!$request->data()->json){
                return redirect($response);
            }else{
                $resource=AssetResource::from($response);
                return response()->json($resource,$resource->responseStatus);
            }
        }
    }

    /**
     * Download the original asset
     * @param AssetInfoRequest $request
     * @return JsonResponse
     */
    public function downloadOriginalAsset(AssetInfoRequest $request):JsonResponse
    {
        //check availability
        $response=$this->assetService->downloadAsset($request->data()->id);
        $resource=AssetResource::from($response);
        return response()->json($resource,$resource->responseStatus);
    }
}