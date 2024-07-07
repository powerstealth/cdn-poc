<?php

namespace Modules\Playlist\Presentation\Api\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller as Controller;
use Modules\Playlist\Domain\Services\PlaylistService;
use Modules\Playlist\Presentation\Api\Requests\SetPlaylistRequest;
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
     * Set a playlist
     * @param SetPlaylistRequest $request
     * @param string             $section
     * @return JsonResponse|null
     */
    public function setPlaylist(SetPlaylistRequest $request, string $section):null|JsonResponse
    {
        $response=$this->playlistService->setPlaylistContents(
            $request->data()->items,
            $section
        );
        $resource=PlaylistResource::from($response);
        return response()->json($resource,$resource->responseStatus);
    }

}