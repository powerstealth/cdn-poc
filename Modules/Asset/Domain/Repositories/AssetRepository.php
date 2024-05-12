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
     * @param string      $status
     * @param string      $title
     * @param string      $description
     * @param string      $fileName
     * @param string|null $key
     * @param string|null $uploadId
     * @param array       $presignedUrls
     * @param int         $fileLength
     * @param bool        $clyUpTv
     * @param bool        $clyUpFrontStore
     * @return Asset|\Exception
     */
    public function createAssetFromUpload(
        string $status,
        string $title,
        string $description,
        string $fileName,
        ?string $key,
        ?string $uploadId,
        array $presignedUrls,
        int $fileLength,
        bool $clyUpTv,
        bool $clyUpFrontStore
    ): Asset|\Exception{
        $asset=new Asset();
        $asset->status=$status;
        $asset->file_name=$fileName;
        $asset->data=[
            'title'=>$title,
            'description'=>$description
        ];
        $asset->ingest=[
            's3'=>[
                'key'=>$key ?? null,
                'upload_id'=>$uploadId ?? null,
                'presigned_urls'=>$presignedUrls ?? []
            ],
            'file'=>[
                'original_filename'=>$fileName,
                'length'=>$fileLength
            ],
        ];
        $asset->clyup_tv=$clyUpTv;
        $asset->clyup_front_store=$clyUpFrontStore;
        $asset->save();
        return $asset;
    }

    /**
     * Get an asset
     * @param string $id
     * @return Asset
     */
    public function getAsset(string $id):Asset
    {
        return Asset::find($id);
    }

    /**
     * Delete an asset
     * @param string      $id
     * @param string|null $status
     * @return bool
     */
    public function deleteAsset(string $id, ?string $status=null):bool
    {
        $asset=Asset::find($id);
        if($status!==null){
            $asset->status=$status;
            $asset->save();
        }
        return $asset->delete();
    }

    /**
     * @param array $filters
     * @return array
     */
    public function listAssets(array $filters):array
    {
        $assets=new Asset();
        //add filters
        if(count($filters)>0){
            foreach ($filters as $filter){
                $assets=$assets->where($filter[0],$filter[1],$filter[2]);
            }
        }
        $assets=$assets->get()->toArray();
        return $assets;
    }

}