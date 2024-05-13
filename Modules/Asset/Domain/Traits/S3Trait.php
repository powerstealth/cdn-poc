<?php
namespace Modules\Asset\Domain\Traits;

use Aws\S3\S3Client;
use Illuminate\Support\Facades\Log;

trait S3Trait{

    /**
     * Initialize S3 Client
     * @return S3Client
     */
    public static function initS3Client():S3Client{
        return new S3Client([
            'credentials'   => [
                'key'       => env('AWS_ACCESS_KEY_ID'),
                'secret'    => env('AWS_SECRET_ACCESS_KEY'),
            ],
            'region'    => env('AWS_DEFAULT_REGION'),
            'endpoint'  => env('AWS_ENDPOINT'),
        ]);
    }


}