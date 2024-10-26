<?php
namespace Modules\Playlist\Domain\Repositories;

use Modules\Auth\Domain\Models\User;
use Modules\Playlist\Domain\Contracts\PlaylistRepositoryInterface;
use Modules\Playlist\Domain\Dto\PlaylistItemDataDto;
use Modules\Playlist\Domain\Models\Playlist;

class PlaylistRepository implements PlaylistRepositoryInterface
{
    /**
     * Constructor
     */
    public function __construct(){}

    /**
     * Get the playlist
     * @param string      $section
     * @param string|null $user
     * @return array|\Exception
     */
    public function getPlaylist(string $section, ?string $user=null):array|\Exception
    {
        try {
            $playlistItems = [];
            //select
            $contents=Playlist::select('*')
                ->with(['asset:_id,data,base_path'])
                ->where('section',$section);
            //filter by user
            if($user!==null)
                $contents->where('created_by',new \MongoDB\BSON\ObjectId($user));
            //sort query
            $contents->orderBy('position','asc');
            //get the items
            $items=$contents->get();
            //remove items without assets
            foreach ($items as $item)
                if($item->asset)
                    $playlistItems[]=$item;
            return $playlistItems;
        }catch (\Exception $e){
            return $e;
        }
    }

    /**
     * Set a Virtual Show playlist
     * @param array       $items
     * @param string      $section
     * @param string      $user
     * @return bool|\Exception
     */
    public function setPlaylist(array $items, string $section, string $user):bool|\Exception
    {
        try {
            //purge section items
            Playlist::where('section',$section)->where('created_by',new \MongoDB\BSON\ObjectId($user))->forceDelete();
            //populate
            foreach ($items as $item) {
                $contentDto=new PlaylistItemDataDto(new \MongoDB\BSON\ObjectId($item["id"]),$section,(int)$item["position"],new \MongoDB\BSON\ObjectId($user));
                Playlist::create($contentDto->toArray());
            }
            return true;
        }catch (\Exception $e){
            return $e;
        }
    }
}