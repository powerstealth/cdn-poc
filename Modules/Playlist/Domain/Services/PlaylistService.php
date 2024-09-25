<?php

namespace Modules\Playlist\Domain\Services;

use Modules\Asset\Domain\Models\Asset;
use Modules\Asset\Domain\Traits\S3Trait;
use Modules\Auth\Domain\Models\User;
use Modules\Playlist\Domain\Dto\PlaylistStreamDto;
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
     * @param string      $section
     * @param string|null $user
     * @return array
     */
    public function getPlaylistContents(string $section, ?string $user=null):array{
        $data=$this->playlistRepository->getPlaylist($section, $user);
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
     * @param string $user
     * @return array
     */
    public function setPlaylistContents(array $items, string $section, string $user):array{
        if($section == 'virtual-show'){
            $check=$this->_checkPrivatePlaylist($items, $user);
            if(!$check)
                return [
                    "success"=>false,
                    "message"=>"",
                    "data"=>[],
                    "error"=>"The assets are wrong",
                    "response_status"=>400
                ];
        }
        $this->playlistRepository->setPlaylist($items, $section, $user);
        return [
            "success"=>true,
            "message"=>"",
            "data"=>[],
            "error"=>"",
            "response_status"=>200
        ];
    }

    /**
     * Playlist streaming
     * @param string      $section
     * @param string|null $userId
     * @param bool        $userMandatory
     * @return array
     */
    public function streamPlaylist(string $section, ?string $userId = null, bool $userMandatory = false):array{
        //check user
        $user=null;
        if(preg_match('/^[0-9a-f]{24}$/i', $userId)){
            $user=$userId;
        }elseif(is_numeric($userId)){
            $user=User::where("magento_user_id",$userId)->first();
            if(isset($user->magento_user_id))
                $user=$user->_id;
        }
        if($userMandatory && $user===null)
            return [
                "success"=>false,
                "message"=>"User unknown",
                "data"=>null,
                "error"=>null,
                "response_status"=>400
            ];
        //select playlist
        $data=$this->playlistRepository->getPlaylist($section, $user);
        $playlist=[];
        foreach ($data as $item)
            $playlist[] = new PlaylistStreamDto(
                $item->asset["data"]["title"] ?? "",
                    $item->asset["data"]["description"] ?? "",
                    $item->asset["media"]["hls"] ?? "",
                    $item->asset["media"]["key_frame"]["HD"] ?? "",
                    $item->asset["media"]["key_frame"]["SD"] ?? "",
                    $item->asset["media"]["key_frame"]["THUMBNAIL"] ?? ""
            );
        return [
            "success"=>true,
            "message"=>"",
            "data"=>$playlist,
            "error"=>"",
            "response_status"=>200
        ];
    }

    /**
     * Check playlist items
     * @param array  $items
     * @param string $user
     * @return bool
     */
    private function _checkPrivatePlaylist(array $items, string $user):bool{
        foreach ($items as $item){
            if(
                !Asset::where('_id',new \MongoDB\BSON\ObjectId($item["id"]))
                    ->where('owner_id',new \MongoDB\BSON\ObjectId($user))
                    ->exists()
            )
                return false;
        }
        return true;
    }
}