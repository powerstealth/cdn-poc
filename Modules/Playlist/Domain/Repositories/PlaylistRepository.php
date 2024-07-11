<?php
namespace Modules\Playlist\Domain\Repositories;

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
     * @param string $section
     * @return array|\Exception
     */
    public function getPlaylist(string $section):array|\Exception
    {
        try {
            $playlistItems = [];
            //select
            $contents=Playlist::select('*')
                ->with(['asset:_id,data'])
                ->where('section',$section);
            //sort query
            $contents->orderBy('position','asc');
            //get the items
            $items=$contents->get();
            //remove items without assets
            foreach ($items as $item)
                if($item->asset)
                    $playlistItems[]=$item;
            return $playlistItems;
        }catch (\Exception $e){dd($e);
            return $e;
        }
    }

    /**
     * Set a playlist
     * @param array  $items
     * @param string $section
     * @return bool|\Exception
     */
    public function setPlaylist(array $items, string $section):bool|\Exception
    {
        try {
            //purge section items
            Playlist::where('section',$section)->delete();
            //populate
            foreach ($items as $item) {
                $contentDto=new PlaylistItemDataDto(new \MongoDB\BSON\ObjectId($item["id"]),$section,(int)$item["position"]);
                Playlist::create($contentDto->toArray());
            }
            return true;
        }catch (\Exception $e){dd($e);
            return $e;
        }
    }
}