<?php

namespace Modules\Asset\Domain\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Asset\Domain\Enums\AssetStatusEnum;
use Modules\Asset\Domain\Repositories\AssetRepository;

class AssetService
{
    protected AssetRepository $assetRepository;

    /**
     * Constructor
     * @param AssetRepository $assetRepository
     */
    public function __construct(AssetRepository $assetRepository){
        $this->assetRepository=$assetRepository;
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
        $fileName=Str::orderedUuid()."";
        $presignedUrl = Storage::disk('s3')->temporaryUploadUrl($fileName, now()->addMinutes(60));
        $asset=$this->assetRepository->createAssetFromUpload(AssetStatusEnum::UPLOAD->name,"","",$fileName,$presignedUrl["url"],$fileLength,$clyUpTv,$clyUpFrontStore);
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

}