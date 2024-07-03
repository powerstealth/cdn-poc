<?php

namespace Modules\Tv\Domain\Services;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Asset\Domain\Enums\AssetTrashedStatusEnum;
use Modules\Asset\Domain\Traits\S3Trait;
use Modules\Tv\Domain\Repositories\HomeRepository;

class HomeService
{
    use S3Trait;

    protected HomeRepository $homeRepository;

    /**
     * Constructor
     * @param HomeRepository $homeRepository
     */
    public function __construct(
        HomeRepository $homeRepository,
    ){
        //initialize the home repository
        $this->homeRepository=$homeRepository;
    }

    /**
     * Get home content list
     * @param string $section
     * @return array
     */
    public function getHomeContents(string $section):array{
        $data=$this->homeRepository->getHomeList($section);
        return [
            "success"=>true,
            "message"=>"",
            "data"=>$data,
            "error"=>"",
            "response_status"=>200
        ];
    }

    /**
     * Set the home contents
     * @param array  $items
     * @param string $section
     * @return array
     */
    public function setHomeContents(array $items, string $section):array{
        $data=$this->homeRepository->setHomeList($items, $section);
        return [
            "success"=>true,
            "message"=>"",
            "data"=>[],
            "error"=>"",
            "response_status"=>200
        ];
    }

}