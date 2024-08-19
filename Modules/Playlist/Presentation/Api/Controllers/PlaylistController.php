<?php

namespace Modules\Playlist\Presentation\Api\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller as Controller;
use Modules\Playlist\Domain\Services\PlaylistService;
use Modules\Playlist\Presentation\Api\Requests\GetPersonalPlaylistRequest;
use Modules\Playlist\Presentation\Api\Requests\SetPlaylistRequest;
use Modules\Playlist\Presentation\Api\Requests\VirtualShowRequest;
use Modules\Playlist\Presentation\Api\Resources\PlaylistResource;

class PlaylistController extends Controller
{
    /**
     * @var PlaylistService
     */
    protected PlaylistService $playlistService;

    /**
     * @param PlaylistService $playlistService
     */
    public function __construct(PlaylistService $playlistService)
    {
        $this->playlistService=$playlistService;
    }

    /**
     * Get a playlist
     * @param Request $request
     * @return JsonResponse|null
     */
    public function getPlaylist(Request $request):null|JsonResponse
    {
        $response=$this->playlistService->getPlaylistContents(
            $request->section,
        );
        $resource=PlaylistResource::from($response);
        return response()->json($resource,$resource->responseStatus);
    }

    /**
     * Get Virtual Show playlist
     * @param Request $request
     * @return JsonResponse|null
     */
    public function getVirtualShowPlaylist(Request $request):null|JsonResponse
    {
        $response=$this->playlistService->getPlaylistContents(
            'virtual-show',
            auth('sanctum')->user()->id
        );
        $resource=PlaylistResource::from($response);
        return response()->json($resource,$resource->responseStatus);
    }

    /**
     * Playlist streaming
     * @param Request $request
     * @return JsonResponse|null
     */
    public function streamPlaylist(Request $request):null|JsonResponse
    {
        $response=$this->playlistService->streamPlaylist(
            $request->section,
        );
        if($response["success"] === true){
            $resource=$response["data"];
        }else{
            $resource=[];
        }
        return response()->json($resource,200);
    }

    /**
     * Stream the Virtual Show
     * @param VirtualShowRequest $request
     * @return JsonResponse|null
     */
    public function streamVirtualShowPlaylist(Request $request):null|JsonResponse
    {
        if($request->user === null){
            $resource=[];
        }else{
            $response=$this->playlistService->streamPlaylist(
                'virtual-show',
                $request->user
            );
            if($response["success"] === true){
                $resource=$response["data"];
            }else{
                $resource=[];
            }
        }
        return response()->json($resource,200);
    }

    /**
     * Set a playlist
     * @param SetPlaylistRequest $request
     * @param string             $section
     * @return JsonResponse|null
     */
    public function setPlaylist(SetPlaylistRequest $request, string $section):null|JsonResponse
    {
        $response=$this->playlistService->setPlaylistContents(
            $request->data()->items,
            $section,
            auth('sanctum')->user()->id
        );
        $resource=PlaylistResource::from($response);
        return response()->json($resource,$resource->responseStatus);
    }

    /**
     * Set a Virtual Show Playlist
     * @param SetPlaylistRequest $request
     * @return JsonResponse|null
     */
    public function setVirtualShowPlaylist(SetPlaylistRequest $request):null|JsonResponse
    {
        $response=$this->playlistService->setPlaylistContents(
            $request->data()->items,
            'virtual-show',
            auth('sanctum')->user()->id
        );
        $resource=PlaylistResource::from($response);
        return response()->json($resource,$resource->responseStatus);
    }

}