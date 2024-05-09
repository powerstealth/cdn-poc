<?php
namespace Modules\Asset\Domain\Repositories;

use Modules\Asset\Domain\Models\Asset;
use Modules\Asset\Domain\Contracts\AssetRepositoryInterface;

class AssetRepository implements AssetRepositoryInterface
{
    /**
     * Constructor
     */
    public function __construct(){}

    /**
     * Create a new asset from upload process
     * @param string $status
     * @param string $title
     * @param string $description
     * @param string $s3SessionKey
     * @param string $fileType
     * @param int    $fileLength
     * @param bool   $clyUpTv
     * @param bool   $clyUpFrontStore
     * @return Asset|\Exception
     */
    public function createAssetFromUpload(
        string $status,
        string $title,
        string $description,
        string $s3SessionKey,
        string $fileType,
        int $fileLength,
        bool $clyUpTv,
        bool $clyUpFrontStore
    ): Asset|\Exception{
        $asset=new Asset();
        $asset->status=$status;
        $asset->data=[
            'title'=>$title,
            'description'=>$description
        ];
        $asset->ingest=[
            's3'=>[
                'session_key'=>$s3SessionKey
            ],
            'file'=>[
                'type'=>$fileType,
                'length'=>$fileLength
            ],
        ];
        $asset->clyup_tv=$clyUpTv;
        $asset->clyup_front_store=$clyUpFrontStore;
        $asset->save();
        return $asset;
    }

}