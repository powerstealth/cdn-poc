<?php

namespace Modules\Asset\Domain\Services;

use Carbon\Carbon;
use Aws\S3\S3Client;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Asset\Domain\Dto\AssetDataDto;
use Modules\Asset\Domain\Dto\PaginationDto;
use Modules\Asset\Domain\Enums\AssetTrashedStatusEnum;
use Modules\Asset\Domain\Models\Asset;
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
        $data=$this->assetRepository->listAssets($page,$limit,$sortField,$sortOrder,$filters,AssetTrashedStatusEnum::EXCLUDETRASHED,$setPagination);
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
     * @param array|null  $data
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
        ?array $data
    ):array{
        try {
            switch ($task){
                case 'start':{
                    return $this->_startMultipartUpload($parts,$originalFileName,$fileLength,$clyUpTv,$clyUpFrontStore,AssetDataDto::from($data));
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
     * @param int          $parts
     * @param string       $originalFileName
     * @param int          $fileLength
     * @param bool         $clyUpTv
     * @param bool         $clyUpFrontStore
     * @param AssetDataDto $data
     * @return array
     */
    private function _startMultipartUpload(int $parts, string $originalFileName, int $fileLength, bool $clyUpTv, bool $clyUpFrontStore, AssetDataDto $data):array{
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
        $asset=$this->assetRepository->createAssetFromUpload(AssetStatusEnum::UPLOAD->name,$data->title??"",$data->description??"",$originalFileName,$key,$result['UploadId'],$urls,$fileLength,$clyUpTv,$clyUpFrontStore,auth('sanctum')->user()->id);
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

    /**
     * Get a single asset
     * @param string $id
     * @return array
     */
    public function getAsset(string $id):array{
        $data=$this->assetRepository->getAsset($id);
        if($data instanceof \Exception){
            $requestData=[
                "success"=>false,
                "message"=>"An error was occurred",
                "data"=>null,
                "error"=>$data->getMessage(),
                "response_status"=>400
            ];
        }else{
            $requestData=[
                "success"=>true,
                "message"=>"",
                "data"=>$data->toArray(),
                "error"=>null,
                "response_status"=>200
            ];
        }
        return $requestData;
    }

    /**
     * Update the asset
     * @param string    $id
     * @param array     $data
     * @param bool|null $published
     * @return array
     */
    public function updateAsset(string $id, array $data, ?bool $published):array{
        //update the asset
        $data=$this->assetRepository->updateAsset($id,null,$data,null,$published);
        //set visibility
        if($published!==null)
            $this->_setPhysicalAssetVisibility($id,$published);
        //return
        if(!$data instanceof \Exception){
            return [
                "success"=>true,
                "message"=>"The asset has been updated successfully",
                "data"=>null,
                "error"=>null,
                "response_status"=>200
            ];
        }else{
            return [
                "success"=>false,
                "message"=>"An error was occurred",
                "data"=>null,
                "error"=>$data->getMessage(),
                "response_status"=>400
            ];
        }
    }

    /**
     * Delete an asset
     * @param string  $id
     * @param bool $hard
     * @return array
     */
    public function deleteAsset(string $id,bool $hard=false):array{
        if($hard) //remove physical files
            $this->_purgeAsset($id);
        else //disable the physical files
            $this->_disableAsset($id);
        //remove asset
        $data=$this->assetRepository->deleteAsset($id,null,$hard);
        if($data instanceof \Exception){
            $requestData=[
                "success"=>false,
                "message"=>"An error was occurred",
                "data"=>null,
                "error"=>$data->getMessage(),
                "response_status"=>400
            ];
        }else{
            $requestData=[
                "success"=>true,
                "message"=>"The asset has been deleted successfully",
                "data"=>null,
                "error"=>null,
                "response_status"=>200
            ];
        }
        return $requestData;
    }

    /**
     * Purge deleted assets
     * @return void
     */
    public function purgeDeletedAssets():void{
        //get all deleted assets
        $filters=[['deleted_at','<',Carbon::now()->subDays(1)]];
        $deletedAssets=$this->assetRepository->listAssets(0,100,'_id','asc',$filters,AssetTrashedStatusEnum::ONLYTRASHED,false);
        foreach($deletedAssets as $deletedAsset){
            $this->deleteAsset($deletedAsset["_id"],true);
        }
    }

    /**
     * Enable or disable the redirect for the streaming
     * @param string $assetId
     * @return bool|string
     */
    public function canStreamAsset(string $assetId):false|string
    {
        //set base stream
        $stream=$assetId."/stream/index.m3u8";
        //check if the asset is published
        if($this->assetRepository->isAssetPublished($assetId)){
            return env("AWS_MEDIA_URL").$stream;
        }else{
            //get the asset
            $asset=$this->getAsset($assetId);
            if($asset===null){
                return false;
            }else{
                return Storage::disk('s3_media')->temporaryUrl(
                    $stream, now()->addMinutes(60)
                );
            }
        }
    }

    /**
     * Purge asset
     * @param string $assetId
     * @return void
     */
    private function _purgeAsset(string $assetId):void
    {
        $objects = $this->s3Client->listObjectsV2([
            'Bucket' => env("AWS_BUCKET_MEDIA"),
            'Prefix' => $assetId."/",
        ]);
        if ($objects['KeyCount'] > 0) {
            $objectsToDelete = [];
            foreach ($objects['Contents'] as $object) {
                $objectsToDelete[] = ['Key' => $object['Key']];
            }
            $this->s3Client->deleteObjects([
                'Bucket'  => env("AWS_BUCKET_MEDIA"),
                'Delete' => [
                    'Objects' => $objectsToDelete,
                ],
            ]);
        }
    }

    /**
     * Enable or disable the physical asset
     * @param string $assetId
     * @param bool   $visibility
     * @return void
     */
    private function _setPhysicalAssetVisibility(string $assetId, bool $visibility):void
    {
        //Set asset's privacy to the filesystem
        Storage::disk('s3_media')->setVisibility($assetId."/stream/index.m3u8",$visibility ? "public" : "private");
    }
}