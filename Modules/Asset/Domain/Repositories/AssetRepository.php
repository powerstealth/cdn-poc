<?php
namespace Modules\Asset\Domain\Repositories;

use Modules\Asset\Domain\Dto\AssetDataDto;
use Modules\Asset\Domain\Enums\AssetStatusEnum;
use Modules\Asset\Domain\Enums\AssetTrashedStatusEnum;
use Modules\Asset\Domain\Models\Asset;
use Modules\Asset\Domain\Contracts\AssetRepositoryInterface;
use Modules\Auth\Domain\Models\User;

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
     * @param array       $tags
     * @param string      $fileName
     * @param string|null $key
     * @param string|null $uploadId
     * @param array       $presignedUrls
     * @param int         $fileLength
     * @param string      $owner
     * @param string      $verified
     * @param bool        $published
     * @return Asset|\Exception
     */
    public function createAssetFromUpload(
        string $status,
        string $title,
        string $description,
        array $tags,
        string $fileName,
        ?string $key,
        ?string $uploadId,
        array $presignedUrls,
        int $fileLength,
        string $owner,
        string $verification,
        bool $published=false,
    ): Asset|\Exception{
        $asset=new Asset();
        $asset->owner_id=new \MongoDB\BSON\ObjectId($owner);
        $asset->published=$published;
        $asset->status=$status;
        $asset->verification=$verification;
        $asset->file_name=$fileName;
        $asset->data=[
            'title'=>$title,
            'description'=>$description,
        ];
        $asset->tags=$tags;
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
            $asset=Asset::where('_id',new \MongoDB\BSON\ObjectId($id))->with(['owner']);
            //filter by user
            if($user!==null && !$user->hasRole('admin'))
                $asset->where('owner_id',new \MongoDB\BSON\ObjectId($user->id));
            //find
            $asset=$asset->first();
            if($asset===null) {
                throw new \Exception("The asset doesn't exist");
            }else{
                return $asset;
            }
        }catch (\Exception $e){;
            return $e;
        }
    }

    /**
     * Delete an asset
     * @param string      $id
     * @param string|null $status
     * @param bool        $hard
     * @return bool
     */
    public function deleteAsset(string $id, ?string $status=null, bool $hard=false):bool
    {
        //get user
        $user=auth('sanctum')->user();
        //get the asset
        $asset=Asset::where('_id',new \MongoDB\BSON\ObjectId($id))->withTrashed();
        //filter by user
        if($user!==null && !$user->hasRole('admin'))
            $asset->where('owner_id',new \MongoDB\BSON\ObjectId($user->id));
        //find
        $asset=$asset->first();
        //set status
        if(isset($asset->status) && $asset->status!==null){
            $asset->status=$status;
        }
        //set published
        $asset->published=false;
        //save the asset
        $asset->save();
        //check hard or soft delete
        if($hard){
            $asset->forceDelete();
            return true;
        }else{
            $asset->delete();
            return true;
        }
    }

    /**
     * Assets list
     * @param int                    $page
     * @param int                    $limit
     * @param string                 $sortField
     * @param string                 $sortOrder
     * @param array                  $filters
     * @param string|null            $search
     * @param AssetTrashedStatusEnum $trashedItems
     * @param bool                   $setPagination
     * @return array|\Exception
     */
    public function listAssets(int $page, int $limit, string $sortField, string $sortOrder, array $filters, ?string $search, AssetTrashedStatusEnum $trashedItems=AssetTrashedStatusEnum::EXCLUDETRASHED ,bool $setPagination=true):array|\Exception
    {
        try {
            //get user
            $user=auth('sanctum')->user();
            //select
            $assets=Asset::select('*')->with(['owner']);
            //manage trashed items
            switch ($trashedItems->value){
                case 1: $assets->withTrashed();break;
                case 2: $assets->onlyTrashed();break;
            }
            //filter by user
            if($user!==null && !$user->hasRole('admin'))
                $assets->where('owner_id',new \MongoDB\BSON\ObjectId($user->id));
            //add filters
            if(count($filters)>0){
                foreach ($filters as $filter){
                    if($filter[2]!==null){
                        if($filter[1]=="in"){
                            $assets=$assets->whereIn($filter[0],(is_array($filter[2]) ? $filter[2] : [$filter[2]]));
                        }else{
                            $assets=$assets->where($filter[0],$filter[1],$filter[2]);
                        }
                    }
                }
            }
            //search
            if($search!==null){
                $assets=$assets->where(function ($query) use ($search) {
                    //title
                    $query->orWhere('data.title', 'like', '%' . $search . '%');
                    //description
                    $query->orWhere('data.description', 'like', '%' . $search . '%');
                    //users
                    $selectUsers=User::orWhere('email', 'like', '%' . $search . '%')
                        ->orWhere('magento_user_id', 'like', '%' . $search . '%')
                        ->pluck('_id')->toArray();
                    $users = array_map(function ($item) {
                        return new \MongoDB\BSON\ObjectId($item);
                    }, $selectUsers);
                    $query->orWhereIn('owner_id', $users);
                });
            }
            //sort query
            $assets->orderBy($sortField,$sortOrder);
            //set pagination
            if($setPagination){
                $assets=$assets->paginate($limit);
                return $assets->toArray();
            }else{
                return $assets->skip($limit*($page-1))->take($limit)->get()->toArray();
            }
        }catch (\Exception $e){
            return $e;
        }
    }

    /**
     * Update an asset
     * @param string      $id
     * @param array|null  $data
     * @param string|null $status
     * @param bool|null   $published
     * @param array|null  $mediaInfo
     * @param string|null $verification
     * @return Asset|\Exception
     */
    public function updateAsset(
        string $id,
        ?array $data,
        ?string $status,
        ?bool $published=null,
        ?array $mediaInfo=null,
        ?string $verification=null
    ):Asset|\Exception
    {
        try {
            //get user
            $user=auth('sanctum')->user();
            //get asset
            if($user===null || $user->hasRole('admin'))
                $asset=Asset::find($id);
            else
                $asset=Asset::where('_id',new \MongoDB\BSON\ObjectId($id))
                    ->where('owner_id',new \MongoDB\BSON\ObjectId($user->id))
                    ->first();
            if($asset===null)
                throw new \Exception("The asset is not available");
            //set the data
            $assetDataDto=new AssetDataDto($data["title"] ?? null,$data["description"] ?? null,$data["tags"] ?? null);
            $asset->data=[
                'title'=>$assetDataDto->title !== null ? $assetDataDto->title : $asset->data['title'] ?? null,
                'description'=>$assetDataDto->description !== null ? $assetDataDto->description : $asset->data['description'] ?? null,
            ];
            //set the tags
            if(isset($data["tags"])){
                if(count($data["tags"])>0){
                    $tagGroups=$asset->tags;
                    foreach ($data["tags"] as $k=>$tags){
                        $tagGroups[$k]=$tags;
                    }
                    $asset->tags=$tagGroups;
                }
            }
            //set the status
            if($status!==null)
                $asset->status=$status;
            //set the asset's verification
            if($verification!==null)
                $asset->verification=$verification;
            //set the published status
            if(isset($published) && $published!==null)
                $asset->published=$published;
            //set media info
            if($mediaInfo!==null)
                $asset->media_info=$mediaInfo;
            //save
            $asset->save();
            return $asset;
        }catch (\Exception $e){
            return $e;
        }
    }

    /**
     * Check if the asset is published
     * @param string $id
     * @return bool
     */
    public function isAssetPublished(string $id):bool
    {
        $asset=Asset::find($id);
        if($asset!==null && isset($asset->published) && $asset->published===true)
            return true;
        else
            return false;
    }

}