<?php
namespace Modules\Asset\Domain\Traits;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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

    /**
     * Get mediainfo
     * @param string $key
     * @return array
     */
    public static function fileMediainfo(string $key):array
    {
        // Get the presigned Url
        $tempUrl = Storage::disk('s3_ingest')->temporaryUrl($key, now()->addSeconds(30));
        // Run Media Info Lib
        $output = shell_exec(env("MEDIAINFO_PATH")." --Output=JSON \"$tempUrl\"");
        return json_decode($output, true);
    }

}