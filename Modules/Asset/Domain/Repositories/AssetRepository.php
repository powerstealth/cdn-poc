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
     * @param string      $owner
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
        bool $clyUpFrontStore,
        string $owner
    ): Asset|\Exception{
        $asset=new Asset();
        $asset->owner=new \MongoDB\BSON\ObjectId($owner);
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
     * @return Asset|null
     */
    public function getAsset(string $id):Asset|\Exception
    {
        try {
            //get user
            $user=auth('sanctum')->user();
            //get asset
            $asset=Asset::where('_id',new \MongoDB\BSON\ObjectId($id));
            //filter by user
            if(!$user->hasRole('admin'))
                $asset->where('owner',new \MongoDB\BSON\ObjectId($user->id));
            //find
            $asset=$asset->first();
            if($asset===null)
                throw new \Exception("The asset doesn't exist");
            else
                return $asset;
        }catch (\Exception $e){;
            return $e;
        }
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
     * Assets list
     * @param int    $page
     * @param int    $limit
     * @param string $sortField
     * @param string $sortOrder
     * @param array  $filters
     * @param bool   $setPagination
     * @return array|\Exception
     */
    public function listAssets(int $page, int $limit, string $sortField, string $sortOrder, array $filters, bool $setPagination):array|\Exception
    {
        try {
            //get user
            $user=auth('sanctum')->user();
            //select
            $assets=Asset::select("*");
            //filter by user
            if(!$user->hasRole('admin'))
                $assets->where('owner',new \MongoDB\BSON\ObjectId($user->id));
            //add filters
            if(count($filters)>0){
                foreach ($filters as $filter){
                    $assets=$assets->where($filter[0],$filter[1],$filter[2]);
                }
            }
            //sort query
            $assets->orderBy($sortField,$sortOrder);
            //set pagination
            if($setPagination){
                $assets=$assets->paginate($limit);
            }else{
                $assets->skip($limit*($page-1))->take($limit)->get();
            }
            return $assets->toArray();
        }catch (\Exception $e){
            return $e;
        }
    }

    /**
     * Update an asset
     * @param string      $id
     * @param array|null  $scope
     * @param array|null  $data
     * @param string|null $status
     * @param array|null  $mediaInfo
     * @return Asset|\Exception
     */
    public function updateAsset(string $id, ?array $scope, ?array $data, ?string $status, ?array $mediaInfo=null):Asset|\Exception
    {
        try {
            //get user
            $user=auth('sanctum')->user();
            //get asset
            if($user->hasRole('admin'))
                $asset=Asset::find($id);
            else
                $asset=Asset::where('_id',new \MongoDB\BSON\ObjectId($id))
                    ->where('owner',new \MongoDB\BSON\ObjectId($user->id))
                    ->first();
            if($asset===null)
                throw new \Exception("The asset is not available");
            //set the scope
            if(isset($scope["clyup_tv"]) && $scope["clyup_tv"]!=null) $asset->clyup_tv=$scope["clyup_tv"];
            if(isset($scope["clyup_front_store"]) && $scope["clyup_front_store"]!=null) $asset->clyup_tv=$scope["clyup_front_store"];
            //set the data
            $newData=$asset->data;
            if($data!==null){
                foreach($data as $k=>$v){
                    if($v!==null)
                        $newData[$k]=$v;
                }
            }
            $asset->data=$newData;
            //set the status
            if($status!==null) $asset->status=$status;
            //set media info
            if($mediaInfo!==null) $asset->media_info=$mediaInfo;
            //save
            $asset->save();
            return $asset;
        }catch (\Exception $e){
            return $e;
        }
    }

}