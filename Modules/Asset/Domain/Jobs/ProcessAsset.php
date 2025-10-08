<?php
namespace Modules\Asset\Domain\Jobs;

use FFMpeg;
use Illuminate\Support\Str;
use Illuminate\Bus\Queueable;
use FFMpeg\Format\Video\X264;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Modules\Asset\Domain\Enums\AssetScopeEnum;
use Modules\Asset\Domain\Enums\AssetTrashedStatusEnum;
use Modules\Asset\Domain\Enums\FrameQualitiesEnum;
use Modules\Asset\Domain\Enums\TranscodingQualityBitrateEnum;
use Modules\Asset\Domain\Models\Asset;
use Modules\Asset\Domain\Traits\S3Trait;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Modules\Asset\Domain\Enums\AssetStatusEnum;
use Modules\Auth\Domain\Models\User;
use Modules\Auth\Domain\Repositories\AuthRepository;
use Modules\Playlist\Domain\Enums\PlaylistSectionEnum;
use Modules\Playlist\Domain\Repositories\PlaylistRepository;
use ProtoneMedia\LaravelFFMpeg\Filters\TileFactory;
use Modules\Asset\Domain\Repositories\AssetRepository;

class ProcessAsset implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, S3Trait;

    const JOBNAME="Process an asset";

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 5;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 60; //seconds

    public function uniqueId(): string
    {
        return "ProcessAsset_".Str::uuid();
    }

    /**
     * Frame interval
     * @var int
     */
    protected int $_frameInterval = 10;

    /**
     * Asset
     * @var string
     */
    protected string $assetId;

    protected AssetRepository $assetRepository;

    protected AuthRepository $authRepository;

    protected PlaylistRepository $playlistRepository;

    /**
     * Constructor
     * @param string $assetId
     */
    public function __construct(string $assetId)
    {
        $this->assetId=$assetId;
        $this->assetRepository=app(AssetRepository::class);
        $this->authRepository=app(AuthRepository::class);
        $this->playlistRepository=app(PlaylistRepository::class);
    }

    /**
     * Execute the job
     */
    public function handle()
    {
        try {
            // Update the asset's status
            $this->assetRepository->updateAsset($this->assetId,null,null,AssetStatusEnum::PROCESS->name);
            // Get the file info from DB
            $asset=$this->assetRepository->getAsset($this->assetId);
            $key=(string)$asset->ingest['s3']['key'];
            $fileLength=(int)$asset->ingest['file']['length'];
            // Get the file from S3 ingest bucket
            $s3Client=self::initS3Client();
            $file = $s3Client->getObject([
                'Bucket' => env("AWS_BUCKET_INGEST"),
                'Key'    => $key,
            ]);
            // Check the file length
            if($fileLength==$file["ContentLength"]){
                // Update the asset's status
                $this->assetRepository->updateAsset($this->assetId,null,AssetStatusEnum::PROCESSING->name);
                // The file length is ok then check if is a video
                $tempUrl = Storage::disk('s3_ingest')->temporaryUrl($key, now()->addSeconds(30));
                if($this->_isVideo($tempUrl)){
                    // Generate base path
                    $basePath=date("Y")."/".date("m")."/".date("d")."/".date("h");
                    // Create presigned Url
                    $presignedUrl = $this->_generatePresignedUrl($key);
                    // Get media info
                    $this->_getMediaInfo($presignedUrl);
                    // Set this video for the storefront
                    $this->_attachAssetToUserPlaylist($this->assetId);
                    // Create tile
                    $this->_createTile($basePath, $presignedUrl);
                    // Create HD frames
                    $this->_createFrames($basePath, $presignedUrl, FrameQualitiesEnum::HD);
                    // Create SD frames
                    $this->_createFrames($basePath, $presignedUrl, FrameQualitiesEnum::SD);
                    // Create thumbnails frames
                    $this->_createFrames($basePath, $presignedUrl, FrameQualitiesEnum::THUMBNAIL);
                    // Convert video to HLS
                    $this->_convertVideoToHls($basePath, $presignedUrl);
                    // Move the original file
                    $this->_moveOriginalFile($basePath, $key, $asset->file_name);
                    // Delete all user's storefront video
                    $this->_deleteAllFrontStoreVideoForUser($this->assetId);
                    // Move a copy of original file and create xml for arkki evo
                    $this->_saveForArkki($this->assetId, $basePath, $key, $asset->file_name);

                }else{
                    throw new \Exception("The file is not a video");
                }
            }else{
                throw new \Exception("The file length is wrong, upload it again. Source ".$fileLength." bytes - Destination ".$file["ContentLength"]." bytes");
            }
            // Update the asset's metadata
            $this->assetRepository->updateAsset($this->assetId,null,AssetStatusEnum::COMPLETED->name);
            // Set the base path
            $this->assetRepository->setAssetBasePath($this->assetId,$basePath ?? '');
        }catch (\Exception $e){
            // On error
            $this->assetRepository->updateAsset($this->assetId,null,null,AssetStatusEnum::ERROR->name);
            // Set the base path
            $this->assetRepository->setAssetBasePath($this->assetId,$basePath ?? '');
            $this->fail($e);
        }catch (\Error $e){
            // On error
            $this->assetRepository->updateAsset($this->assetId,null,null,AssetStatusEnum::ERROR->name);
            // Set the base path
            $this->assetRepository->setAssetBasePath($this->assetId,$basePath ?? '');
            $this->fail($e);
        }
    }

    /**
     * Error event
     * @param \Throwable $e
     * @return void
     */
    public function failed(\Throwable $e)
    {
        //fails the job
        Log::error("The job is failed: ".$e->getMessage());
        Log::debug(json_encode([$e->getCode(), $e->getFile()."#".$e->getLine(),$e->getTrace()]));
        $this->assetRepository->updateAsset($this->assetId,null,AssetStatusEnum::ERROR->name);
    }

    /**
     * Check if the file is a video
     * @param $url
     * @return bool
     */
    private function _isVideo($url):bool
    {
        try {
            $output = shell_exec(env("MEDIAINFO_PATH")." --Output=JSON \"$url\"");
            $data = json_decode($output, true);
            $isVideo = false;
            if ($data !== null && isset($data['media']['track'])) {
                foreach ($data['media']['track'] as $track) {
                    if (isset($track['@type']) && $track['@type'] === 'Video') {
                        $isVideo = true;
                        break;
                    }
                }
            }
            return $isVideo;
        }catch (\Exception $e){
            return false;
        }
    }

    /**
     * Convert a video to HLS
     * @param string $basePath
     * @param string $tempUrl
     * @return void
     */
    private function _convertVideoToHls(string $basePath, string $tempUrl):void
    {
        // Get video
        $video=FFMpeg::openUrl($tempUrl);
        // Get orientation
        $dimensions=$video->getVideoStream()->getDimensions();
        $width=(int)$dimensions->getWidth();
        $height=(int)$dimensions->getHeight();
        // Set bitrates and sizes
        $bitrate=$video->getVideoStream()->get("bit_rate")/1000;
        $profiles['SD'] = [
            'bitrate' => (new X264)->setKiloBitrate(1000),
            'size' => ($width > $height) ? 'scale='.TranscodingQualityBitrateEnum::SD->value.':-2' : 'scale='.TranscodingQualityBitrateEnum::XSD->value.':-2'
        ];
        if($bitrate>=2000)
            $profiles['HD'] = [
                'bitrate' => (new X264)->setKiloBitrate(2000),
                'size' => ($width > $height) ? 'scale='.TranscodingQualityBitrateEnum::HD->value.':-2' : 'scale='.TranscodingQualityBitrateEnum::SD->value.':-2'
            ];
        if($bitrate>=4000)
            $profiles['FHD'] = [
                'bitrate' => (new X264)->setKiloBitrate(4000),
                'size' => ($width > $height) ? 'scale='.TranscodingQualityBitrateEnum::FHD->value.':-2' : 'scale='.TranscodingQualityBitrateEnum::HD->value.':-2'
            ];
        unset($video);
        $transcoder=FFMpeg::openUrl($tempUrl)
            ->exportForHLS()
            ->setSegmentLength(5)
            ->toDisk('s3_media');
        // Start the transcoding
        foreach($profiles as $profile){
            $transcoder=$transcoder->addFormat($profile['bitrate'], function($media) use ($profile){
                $media->addFilter($profile['size']);
            });
        }
        $transcoder->save($basePath.$this->assetId.'/stream/index.m3u8');
    }

    /**
     * Create the tile
     * @param string $basePath
     * @param string $tempUrl
     * @return void
     */
    private function _createTile(string $basePath, string $tempUrl):void
    {
        //set the transcoder
        $transcoder=FFMpeg::openUrl($tempUrl)
            ->exportTile(function (TileFactory $factory) use ($basePath){
                $factory->interval($this->_frameInterval)
                    ->scale(320, 180)
                    ->grid(5, 2)
                    ->generateVTT($basePath.$this->assetId.'/tile/tile.vtt');
            })
            ->toDisk('s3_media');
        //save
        $transcoder->save($basePath.$this->assetId.'/tile/tile_%05d.jpg');
    }

    /**
     * Create the frames
     * @param string             $basePath
     * @param string             $tempUrl
     * @param FrameQualitiesEnum $quality
     * @return void
     */
    private function _createFrames(string $basePath, string $tempUrl, FrameQualitiesEnum $quality):void
    {
        //set the width
        $width=$quality->value;
        //set the 16:9 with letterbox
        $height=(int)($width/16*9);
        $fLetterbox="scale={$width}:{$height}:force_original_aspect_ratio=decrease,pad={$width}:{$height}:(ow-iw)/2:(oh-ih)/2";
        //set the transcoder
        $transcoder=FFMpeg::openUrl($tempUrl)
            ->exportFramesByInterval($this->_frameInterval)
            ->addFilter(function ($filters) use ($fLetterbox){
                $filters->custom($fLetterbox);
            })
            ->toDisk('s3_media');
        //save
        $transcoder->save($basePath.$this->assetId.'/frames/'.$quality->name.'/frame_%05d.jpg');
    }

    /**
     * Move the original file
     * @param string $basePath
     * @param string $key
     * @param string $originalFile
     * @return void
     */
    private function _moveOriginalFile(string $basePath, string $key, string $originalFile):void
    {
        //set the source
        $source=Storage::disk('s3_ingest')->get($key);
        //move
        Storage::disk('s3_media')
            ->put(
                $basePath.$this->assetId."/original/".$originalFile,
                $source,
                [
                    'ContentDisposition' => 'attachment',
                ]
            );
    }

    /**
     * Get media info
     * @param string $tempUrl
     * @return void
     */
    private function _getMediaInfo(string $tempUrl):void
    {
        //run Media Info Lib
        $output = shell_exec(env("MEDIAINFO_PATH")." --Output=JSON \"$tempUrl\"");
        $data = json_decode($output, true);
        if ($data !== null && isset($data['media']['track'])) {
            //Update asset
            $this->assetRepository->updateAsset($this->assetId,null,null,null,$data['media']['track']);
        }
    }

    /**
     * Generate presigned url
     * @param string $key
     * @param int    $minutes
     * @return string
     */
    private function _generatePresignedUrl(string $key, int $minutes=60):string{
        return Storage::disk('s3_ingest')->temporaryUrl($key, now()->addMinutes($minutes));
    }

    /**
     * Create a copy of the original file for Arkki with XML
     * @param        $assetId
     * @param string $basePath
     * @param string $key
     * @param string $originalFile
     * @return bool
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    private function _saveForArkki($assetId, string $basePath, string $key, string $originalFile):bool
    {
        try {
            //create the xml
            $asset=$this->assetRepository->getAsset($assetId);
            if($asset===null)
                throw new \Exception("Asset $assetId not found");
            //set xml
            $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><asset></asset>');
            //add type
            $xml->addChild('type', env('ARKKI_TYPE'));
            //add metadata parent
            $metadata = $xml->addChild('metadata');
            //add id, title, description and owner
            $metadata->addChild('clyupid', $asset['_id'] ?? '');
            $metadata->addChild('asset-title', $asset['data']['title'] ?? $asset['file_name']);
            $metadata->addChild('asset-description', $asset['data']['description'] ?? "");
            $metadata->addChild('asset-owner', $asset['owner_id'] ?? "");
            //save xml to the sync storage for Arkki
            $xmlContent = $xml->asXML();
            $fullPathXml = env('ARKKI_MEDIA_STORAGE').$asset->_id.".xml";
            Log::channel('arkki')->info($fullPathXml);
            Log::channel('arkki')->info($xmlContent);
            Storage::disk('s3_media')->put(
                $fullPathXml,
                $xmlContent,
                [
                    'ContentType' => 'application/xml',
                    'ContentDisposition' => 'attachment',
                ]
            );
            //save original video to the sync storage for Arkki
            $sourcePath = $basePath . $this->assetId . "/original/" . $originalFile;
            $destinationPath = env('ARKKI_MEDIA_STORAGE') . $this->assetId . '.' . pathinfo($originalFile, PATHINFO_EXTENSION);
            if (Storage::disk('s3_media')->exists($sourcePath)) {
                // Copy the file to S3
                Storage::disk('s3_media')->put(
                    $destinationPath,
                    Storage::disk('s3_media')->get($sourcePath),
                    [
                        'ContentDisposition' => 'attachment',
                    ]
                );
            } else {
                throw new Exception("Source file does not exist: " . $sourcePath);
            }
            return true;
        }catch (\Exception $e){
            Log::channel('arkki')->error($e->getMessage());
            Log::channel('arkki')->error($e->getTraceAsString());
            return false;
        }
    }

    /**
     * Delete all users video for storefront
     * @param string $assetId
     * @return bool
     */
    private function _deleteAllFrontStoreVideoForUser(string $assetId): bool {
        try {
            // Get video
            $asset = $this->assetRepository->getAsset($assetId);
            if(!$asset instanceof Asset) {
                throw new \Exception("Asset $assetId not found");
            }
            // Get the user
            $user=$this->authRepository->getUserById($asset->owner_id);
            if(!$user instanceof User) {
                throw new \Exception("User not found");
            }
            // Get all user's frontstore video
            $filters = [
                ["owner_id","=",$user->_id],
            ];
            $assets = $this->assetRepository->listAssets(0,500,"_id","asc",$filters,null,AssetTrashedStatusEnum::WITHTRASHED,false);
            foreach ($assets as $item) {
                if(
                    isset($item['tags']['SCOPE']) &&
                    $item['tags']['SCOPE']==AssetScopeEnum::CLYUP_STOREFRONT->value &&
                    $item['_id'] !== $assetId
                )
                    $this->assetRepository->deleteAsset($item['_id'],null,true);
            }
            return true;
        }catch (\Exception $e){
            return false;
        }
    }

    /**
     * Attach the asset to user's playlist
     * @param string $assetId
     * @return bool
     */
    private function _attachAssetToUserPlaylist(string $assetId): bool {
        try {
            // Get video
            $asset = $this->assetRepository->getAsset($assetId);
            if(!$asset instanceof Asset) {
                throw new \Exception("Asset $assetId not found");
            }
            // Get the user
            $user=$this->authRepository->getUserById($asset->owner_id);
            if(!$user instanceof User) {
                throw new \Exception("User not found");
            }
            $this->playlistRepository->setPlaylist(
                [
                    ["id"=>$assetId, "position"=>0]
                ],
                PlaylistSectionEnum::VIRTUAL_SHOW->value,
                $user->_id
            );
            return true;
        }catch (\Exception $e){
            return false;
        }
    }
}