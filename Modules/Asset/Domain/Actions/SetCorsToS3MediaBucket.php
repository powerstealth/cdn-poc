<?php
namespace Modules\Asset\Domain\Actions;

use Carbon\Carbon;
use Aws\S3\S3Client;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SetCorsToS3MediaBucket
{
    /**
     * Set CORS to Media Bucket
     * @param S3Client $s3Client
     * @param string   $bucket
     * @return void
     */
    public function execute(S3Client $s3Client, string $bucket):void
    {
        try {
            $corsConfiguration = [
                'CORSRules' => [
                    [
                        'AllowedOrigins' => ['*'],
                        'AllowedMethods' => ['GET','PUT','HEAD'],
                        'AllowedHeaders' => ['Authorization','Content-Type'],
                        'MaxAgeSeconds' => 3000,
                    ]
                ]
            ];
            $s3Client->putBucketCors([
                'Bucket' => $bucket,
                'CORSConfiguration' => $corsConfiguration
            ]);
        }catch (\Exception $e){
            echo $e->getMessage();
        }
    }
}