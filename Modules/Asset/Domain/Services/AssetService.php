<?php

namespace Modules\Asset\Domain\Services;

use Aws\S3\S3Client;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Asset\Domain\Enums\AssetStatusEnum;
use Modules\Asset\Domain\Actions\PurgeExpiredUploads;
use Modules\Asset\Domain\Repositories\AssetRepository;

class AssetService
{
    protected AssetRepository $assetRepository;
    protected PurgeExpiredUploads $purgeExpiredUploads;

    protected S3Client $s3Client;

    /**
     * Constructor
     * @param AssetRepository       $assetRepository
     * @param PurgeExpiredUploads $purgeExpiredUploads
     * @param PurgeAllUploads $purgeAllUploads
     */
    public function __construct(
        AssetRepository $assetRepository,
        PurgeExpiredUploads $purgeExpiredUploads,
        PurgeAllUploads $purgeAllUploads
    ){
        //initialize the asset repository
        $this->assetRepository=$assetRepository;
        //initialize the actions repository
        $this->purgeExpiredUploads=$purgeExpiredUploads;
        $this->purgeAllUploads=$purgeAllUploads;
        //initialize S3 client
        $this->s3Client=self::initS3Client();
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
        $presignedUrl = Storage::disk('s3')->temporaryUploadUrl($fileName, now()->addMinutes(60));
        $asset=$this->assetRepository->createAssetFromUpload(AssetStatusEnum::UPLOAD->name,"","",$fileName,null,null,[$presignedUrl["url"]],$fileLength,$clyUpTv,$clyUpFrontStore);
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
            'Bucket'            => env("AWS_BUCKET"),
            'Key'               => $key,
            'ContentDisposition'=> 'inline',
        ]);
        //sign the urls
        $urls=$this->_signMultipartUpload($result['UploadId'],$key,$parts);
        //create the asset
        $asset=$this->assetRepository->createAssetFromUpload(AssetStatusEnum::UPLOAD->name,"","",$originalFileName,$key,$result['UploadId'],$urls,$fileLength,$clyUpTv,$clyUpFrontStore);
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
                'Bucket'     => env("AWS_BUCKET"),
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
            'Bucket'    => env("AWS_BUCKET"),
            'Key'       => $key,
            'UploadId'  => $uploadId,
        ]);
        $parts=[];
        if(isset($uploadedParts["Parts"])){
            foreach ($uploadedParts["Parts"] as $uploadedPart) {
                $parts[]=[
                    'PartNumber'    => $uploadedPart["PartNumber"],
                    'ETag'          => $uploadedPart["ETag"],
                ];
            }
        }
        $result = $this->s3Client->completeMultipartUpload(
            [
                'Bucket'          => env("AWS_BUCKET"),
                'Key'             => $key,
                'UploadId'        => $uploadId,
                'MultipartUpload' => [
                    'Parts' => $parts,
                ],
        ]);
        return [
            "success"=>true,
            "message"=>"",
            "data"=>[
                "location"=>(string) $result['Location']
            ],
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
}