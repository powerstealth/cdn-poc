<?php
namespace Modules\Asset\Domain\Actions;

use Carbon\Carbon;
use Aws\S3\S3Client;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Asset\Domain\Enums\AssetStatusEnum;
use Modules\Asset\Domain\Repositories\AssetRepository;

class PurgeExpiredUploads
{
    /**
     * Purge the Multipart Uploads
     * @param S3Client $s3Client
     * @return void
     */
    public function expiredMultipartUploads(S3Client $s3Client):void
    {
        try {
            //get the list of multipart uploads
            $uploads = $s3Client->listMultipartUploads([
                'Bucket' => env("AWS_BUCKET_INGEST"),
            ]);

            if(isset($uploads["Uploads"])){
                foreach ($uploads["Uploads"] as $upload){
                    $uploadDateTime = Carbon::instance($upload["Initiated"])->addSeconds((int)env("AWS_PRESIGNED_TIME"));
                    $currentDateTime = Carbon::now();
                    if($uploadDateTime->lt($currentDateTime)) {
                        //remove upload
                        $s3Client->abortMultipartUpload([
                            'Bucket'   => env("AWS_BUCKET_INGEST"),
                            'Key'      => $upload["Key"],
                            'UploadId' => $upload["UploadId"],
                        ]);
                    }
                }
            }
        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Remove expired assets
     * @param AssetRepository $assetRepository
     * @return void
     */
    public function expiredAssets(AssetRepository $assetRepository):void
    {
        try {
            $filters=[
                ["status","=",AssetStatusEnum::UPLOAD->name],
                ["created_at","<",Carbon::now()->subSeconds(env("AWS_PRESIGNED_TIME"))]
            ];
            $assets=$assetRepository->listAssets(0,100,'_id','asc',$filters);
            if(count($assets["data"])>0){
                foreach ($assets["data"] as $asset){
                    $assetRepository->deleteAsset($asset["_id"],AssetStatusEnum::ERROR->name);
                }
            }
        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
    }
}