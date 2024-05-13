<?php
namespace Modules\Asset\Domain\Actions;

use Carbon\Carbon;
use Aws\S3\S3Client;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class PurgeAllUploads
{
    /**
     * Purge the Multipart Uploads
     * @param S3Client $s3Client
     * @return void
     */
    public function execute(S3Client $s3Client):void
    {
        try {
            //get the list of multipart uploads
            $uploads = $s3Client->listMultipartUploads([
                'Bucket' => env("AWS_BUCKET_INGEST"),
            ]);
            //remove all partial uploads
            if(isset($uploads["Uploads"])){
                foreach ($uploads["Uploads"] as $upload){
                    //remove upload
                    $s3Client->abortMultipartUpload([
                        'Bucket'   => env("AWS_BUCKET_INGEST"),
                        'Key'      => $upload["Key"],
                        'UploadId' => $upload["UploadId"],
                    ]);
                }
            }
            //remove the uploads
            $uploads = $s3Client->listObjects([
                'Bucket' => env("AWS_BUCKET_INGEST"),
            ]);
            if(isset($uploads["Contents"])){
                foreach ($uploads["Contents"] as $upload){
                    //remove upload
                    $s3Client->deleteObject([
                        'Bucket'   => env("AWS_BUCKET_INGEST"),
                        'Key'      => $upload["Key"]
                    ]);
                }
            }
        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
    }

}