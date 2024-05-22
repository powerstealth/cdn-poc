<?php

namespace Modules\Asset\Domain\Services;

use Aws\S3\S3Client;
use FFMpeg;
use FFMpeg\Format\Video\X264;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Asset\Domain\Dto\PaginationDto;
use Modules\Asset\Domain\Traits\S3Trait;
use Modules\Asset\Domain\Jobs\ProcessAsset;
use Modules\Asset\Domain\Enums\AssetStatusEnum;
use Modules\Asset\Domain\Actions\PurgeAllUploads;
use Modules\Asset\Domain\Actions\PurgeExpiredUploads;
use Modules\Asset\Domain\Actions\SetCorsToS3MediaBucket;
use Modules\Asset\Domain\Repositories\AssetRepository;

class AssetService
{
    use S3Trait;

    protected AssetRepository $assetRepository;
    protected PurgeExpiredUploads $purgeExpiredUploads;
    protected PurgeAllUploads $purgeAllUploads;
    protected SetCorsToS3MediaBucket $setCorsToS3MediaBucket;

    /**
     * S3 Client
     * @var S3Client
     */
    protected S3Client $s3Client;

    /**
     * Constructor
     * @param AssetRepository        $assetRepository
     * @param PurgeExpiredUploads    $purgeExpiredUploads
     * @param PurgeAllUploads        $purgeAllUploads
     * @param SetCorsToS3MediaBucket $setCorsToS3MediaBucket
     */
    public function __construct(
        AssetRepository $assetRepository,
        PurgeExpiredUploads $purgeExpiredUploads,
        PurgeAllUploads $purgeAllUploads,
        SetCorsToS3MediaBucket $setCorsToS3MediaBucket
    ){
        //initialize the asset repository
        $this->assetRepository=$assetRepository;
        //initialize the actions repository
        $this->purgeExpiredUploads=$purgeExpiredUploads;
        $this->purgeAllUploads=$purgeAllUploads;
        $this->setCorsToS3MediaBucket=$setCorsToS3MediaBucket;
        //initialize S3 client
        $this->s3Client=self::initS3Client();
    }

    /**
     * Get assets list
     * @param int    $page
     * @param int    $limit
     * @param string $sortField
     * @param string $sortOrder
     * @param array  $filters
     * @param bool   $setPagination
     * @return array
     */
    public function getAssets(int $page, int $limit, string $sortField, string $sortOrder, array $filters, bool $setPagination):array{
        $data=$this->assetRepository->listAssets($page,$limit,$sortField,$sortOrder,$filters,$setPagination);
        return $this->_returnWithPagination($data,$setPagination);
    }

    /**
     * Get user info
     * @param string $fileName
     * @param int    $fileLength
     * @param bool   $clyUpTv
     * @param bool   $clyUpFrontStore
     * @return array
     */
    public function setUploadSession(string $fileName, int $fileLength, bool $clyUpTv, bool $clyUpFrontStore):array{
        $fileName=Str::orderedUuid();
        $presignedUrl = Storage::disk('s3_ingest')->temporaryUploadUrl($fileName, now()->addMinutes(60));
        $asset=$this->assetRepository->createAssetFromUpload(AssetStatusEnum::UPLOAD->name,"","",$fileName,null,null,[$presignedUrl["url"]],$fileLength,$clyUpTv,$clyUpFrontStore,auth('sanctum')->user()->id);
        return [
            "success"=>true,
            "message"=>"",
            "data"=>[
                "asset_id" => $asset->id,
                "presigned_url" => $presignedUrl["url"]
            ],
            "error"=>"",
            "response_status"=>200
        ];
    }

    /**
     * Set multipart upload
     * @param string      $task
     * @param string|null $originalFileName
     * @param int|null    $fileLength
     * @param bool|null   $clyUpTv
     * @param bool|null   $clyUpFrontStore
     * @param string|null $assetId
     * @param int|null    $parts
     * @return array
     */
    public function setMultipartUpload(
        string $task,
        ?string $originalFileName,
        ?int $fileLength,
        ?bool $clyUpTv,
        ?bool $clyUpFrontStore,
        ?string $assetId,
        ?int $parts,
    ):array{
        try {
            switch ($task){
                case 'start':{
                    return $this->_startMultipartUpload($parts,$originalFileName,$fileLength,$clyUpTv,$clyUpFrontStore);
                }
                case 'complete':{
                    return $this->_completeMultipartUpload($assetId);
                }
                default:{throw new \Exception("The task doesn't exist");break;}
            }
        }catch (\Exception $e){
            //set asset with error
            if(isset($assetId)){
                $asset=$this->assetRepository->getAsset($assetId);
                if($assetId!==null && in_array($asset->status,[AssetStatusEnum::UPLOADED->name]))
                    $this->assetRepository->updateAsset($assetId,null,null,AssetStatusEnum::ERROR->name);
            }
            return [
                "success"=>false,
                "message"=>"",
                "data"=>null,
                "error"=>$e->getMessage(),
                "response_status"=>400
            ];
        }
    }

    /**
     * Start Multipart Upload
     * @param int    $parts
     * @param string $originalFileName
     * @param int    $fileLength
     * @param bool   $clyUpTv
     * @param bool   $clyUpFrontStore
     * @return array
     */
    private function _startMultipartUpload(int $parts, string $originalFileName, int $fileLength, bool $clyUpTv, bool $clyUpFrontStore):array{
        //generate the key
        $key=Str::orderedUuid()->toString();
        //create the session
        $result = $this->s3Client->createMultipartUpload([
            'Bucket'            => env("AWS_BUCKET_INGEST"),
            'Key'               => $key,
            'ContentDisposition'=> 'inline',
        ]);
        //sign the urls
        $urls=$this->_signMultipartUpload($result['UploadId'],$key,$parts);
        //create the asset
        $asset=$this->assetRepository->createAssetFromUpload(AssetStatusEnum::UPLOAD->name,"","",$originalFileName,$key,$result['UploadId'],$urls,$fileLength,$clyUpTv,$clyUpFrontStore,auth('sanctum')->user()->id);
        //return
        return [
            "success"=>true,
            "message"=>"",
            "data"=>[
                "asset_id" => $asset->_id,
                "presigned_urls" => $urls
            ],
            "error"=>"",
            "response_status"=>200
        ];
    }

    /**
     * Signed Url
     * @param string $uploadId
     * @param string $key
     * @param int    $parts
     * @return array
     */
    private function _signMultipartUpload(string $uploadId, string $key, int $parts):array{
        $preSignedUrls=[];
        for($i=1;$i<=$parts;$i++){
            $command = $this->s3Client->getCommand('UploadPart', [
                'Bucket'     => env("AWS_BUCKET_INGEST"),
                'Key'        => $key,
                'UploadId'   => $uploadId,
                'PartNumber' => $i,
                //'ContentType' => 'application/octet-stream',
            ]);
            $result = $this->s3Client->createPresignedRequest($command, time()+env("AWS_PRESIGNED_TIME"));
            $preSignedUrls[$i]=(string) $result->getUri();
        }
        return $preSignedUrls;
    }

    /**
     * Complete the multipart uploads
     * @param string $assetId
     * @return array
     * @throws \Exception
     */
    public function _completeMultipartUpload(string $assetId):array{
        //check if the asset exists
        $asset=$this->assetRepository->getAsset($assetId);
        //get key and upload ID
        $key=$asset->ingest["s3"]["key"];
        $uploadId=$asset->ingest["s3"]["upload_id"];
        if($key==null || $uploadId==null)
            throw new \Exception("Can't process the asset as multipart upload");
        //get upload ID
        $uploadedParts = $this->s3Client->listParts([
            'Bucket'    => env("AWS_BUCKET_INGEST"),
            'Key'       => $key,
            'UploadId'  => $uploadId,
        ]);
        //merge parts
        $parts=[];
        if(isset($uploadedParts["Parts"])){
            foreach ($uploadedParts["Parts"] as $uploadedPart) {
                $parts[]=[
                    'PartNumber'    => $uploadedPart["PartNumber"],
                    'ETag'          => $uploadedPart["ETag"],
                ];
            }
        }
        //complete the multipart upload
        $this->s3Client->completeMultipartUpload(
            [
                'Bucket'          => env("AWS_BUCKET_INGEST"),
                'Key'             => $key,
                'UploadId'        => $uploadId,
                'MultipartUpload' => [
                    'Parts' => $parts,
                ],
                'visibility' => 'public',
        ]);
        //set asset status
        $this->assetRepository->updateAsset($assetId,null,null,AssetStatusEnum::UPLOADED->name);
        //run process job
        ProcessAsset::dispatch($assetId)->onQueue(env("WORKER_ID"));
        return [
            "success"=>true,
            "message"=>"The file has been uploaded successfully",
            "error"=>"",
            "response_status"=>200
        ];
    }

    /**
     * Purge Expired Uploads
     * @return void
     */
    public function purgeExpiredUploads():void
    {
        //remove S3 multipart uploads
        $this->purgeExpiredUploads->expiredMultipartUploads($this->s3Client);
        //remove expired assets
        $this->purgeExpiredUploads->expiredAssets($this->assetRepository);
    }

    /**
     * Purge all uploads
     * @return void
     */
    public function wipeUploads():void
    {
        //remove S3 multipart uploads
        $this->purgeAllUploads->execute($this->s3Client);
    }

    /**
     * Set CORS to Media Bucket
     * @param string $bucket
     * @return void
     */
    public function SetCorsToS3MediaBucket(string $bucket):void
    {
        //remove S3 multipart uploads
        $this->setCorsToS3MediaBucket->execute($this->s3Client,$bucket);
    }

    /**
     * Return
     * @param array|\Exception $data
     * @param bool             $setPagination
     * @return array
     */
    private function _returnWithPagination(array|\Exception $data, bool $setPagination):array
    {
        if($data instanceof \Exception){
            return [
                "success"=>false,
                "message"=>"An error was occurred",
                "data"=>null,
                "error"=>$data->getMessage(),
                "response_status"=>400
            ];
        }else{
            if($setPagination){
                $data=[
                    'items'=>$data["data"],
                    'pagination'=>new PaginationDto(
                        $data["current_page"],
                        $data["last_page"],
                        $data["total"],
                        $data["per_page"],
                        $data["next_page_url"]!=null ? true : false,
                        $data["prev_page_url"]!=null ? true : false,
                    )
                ];
            }
        }
        return [
            "success"=>true,
            "message"=>"",
            "data"=>$data,
            "error"=>null,
            "response_status"=>200
        ];
    }
}