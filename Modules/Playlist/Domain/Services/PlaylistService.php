<?php

namespace Modules\Playlist\Domain\Services;

use Illuminate\Support\Facades\Log;
use Modules\Asset\Domain\Traits\S3Trait;
use Modules\Playlist\Domain\Repositories\PlaylistRepository;

class PlaylistService
{
    use S3Trait;

    protected PlaylistRepository $playlistRepository;

    /**
     * Constructor
     * @param PlaylistRepository $playlistRepository
     */
    public function __construct(
        PlaylistRepository $playlistRepository,
    ){
        //initialize the playlist repository
        $this->playlistRepository=$playlistRepository;
    }

    /**
     * Get playlist content list
     * @param string $section
     * @return array
     */
    public function getPlaylistContents(string $section):array{
        $data=$this->playlistRepository->getPlaylist($section);
        return [
            "success"=>true,
            "message"=>"",
            "data"=>$data,
            "error"=>"",
            "response_status"=>200
        ];
    }

    /**
     * Set the playlist contents
     * @param array  $items
     * @param string $section
     * @return array
     */
    public function setPlaylistContents(array $items, string $section):array{
        $this->playlistRepository->setPlaylist($items, $section);
        return [
            "success"=>true,
            "message"=>"",
            "data"=>[],
            "error"=>"",
            "response_status"=>200
        ];
    }

}