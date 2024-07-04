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
            //select
            $contents=Playlist::select('*')->with(['asset']);
            //sort query
            $contents->orderBy('position','asc');
            return $contents->get()->toArray();
        }catch (\Exception $e){
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
            //purge
            Playlist::truncate();
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