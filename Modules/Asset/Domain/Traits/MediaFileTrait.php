<?php
namespace Modules\Asset\Domain\Traits;

use Illuminate\Support\Facades\Log;

trait MediaFileTrait{

    /**
     * Image qualities
     * @return array
     */
    public static function getAllowedMediaFiles():array{
        return[
            "video/mp4"=>"Mp4",
        ];
    }


}