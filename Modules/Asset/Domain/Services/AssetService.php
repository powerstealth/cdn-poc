<?php

namespace Modules\Asset\Domain\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
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
     * @param int    $fileLength
     * @param string $fileType
     * @param bool   $clyUpTv
     * @param bool   $clyUpFrontStore
     * @return array
     */
    public function setUploadSession(int $fileLength, string $fileType, bool $clyUpTv, bool $clyUpFrontStore):array{
        $sessionKey=(string)rand(100000000000000,999999999999999);
        $asset=$this->assetRepository->createAssetFromUpload(AssetStatusEnum::UPLOAD->name,"","",$sessionKey,$fileType,$fileLength,$clyUpTv,$clyUpFrontStore);
        return [
            "success"=>true,
            "message"=>"",
            "data"=>[
                "asset_id" => $asset->id,
                "session_key" => $sessionKey
            ],
            "error"=>"",
            "response_status"=>200
        ];
    }

}