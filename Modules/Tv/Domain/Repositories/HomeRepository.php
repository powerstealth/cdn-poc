<?php
namespace Modules\Tv\Domain\Repositories;

use Modules\Asset\Domain\Dto\AssetDataDto;
use Modules\Asset\Domain\Enums\AssetTrashedStatusEnum;
use Modules\Asset\Domain\Models\Asset;
use Modules\Tv\Domain\Contracts\HomeRepositoryInterface;
use Modules\Auth\Domain\Models\User;
use Modules\Tv\Domain\Dto\HomeItemDataDto;
use Modules\Tv\Domain\Models\Home;

class HomeRepository implements HomeRepositoryInterface
{
    /**
     * Constructor
     */
    public function __construct(){}

    /**
     * Get home content list
     * @param string $section
     * @return array|\Exception
     */
    public function getHomeList(string $section):array|\Exception
    {
        try {
            //select
            $contents=Home::select('*');
            //sort query
            $contents->orderBy('position','desc');
            return $contents->get()->toArray();
        }catch (\Exception $e){
            return $e;
        }
    }

    /**
     * Set the home content list
     * @param array  $items
     * @param string $section
     * @return bool|\Exception
     */
    public function setHomeList(array $items, string $section):bool|\Exception
    {
        try {
            //purge
            Home::truncate();
            //populate
            foreach ($items as $item) {
                $contentDto=new HomeItemDataDto(new \MongoDB\BSON\ObjectId($item["id"]),$section,(int)$item["position"]);
                Home::create($contentDto->toArray());
            }
            return true;
        }catch (\Exception $e){dd($e);
            return $e;
        }
    }
}