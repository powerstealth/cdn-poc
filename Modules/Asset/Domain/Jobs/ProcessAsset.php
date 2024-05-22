<?php
namespace Modules\Asset\Domain\Jobs;

use FFMpeg;
use Illuminate\Support\Str;
use Illuminate\Bus\Queueable;
use FFMpeg\Format\Video\X264;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Modules\Asset\Domain\Traits\S3Trait;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Modules\Asset\Domain\Enums\AssetStatusEnum;
use ProtoneMedia\LaravelFFMpeg\Filters\TileFactory;
use Modules\Asset\Domain\Repositories\AssetRepository;

class ProcessAsset implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, S3Trait;

    const JOBNAME="Process an asset";

    public function uniqueId(): string
    {
        return "ProcessAsset_".Str::uuid();
    }

    /**
     * Asset
     * @var string
     */
    protected string $assetId;

    /**
     * Downloaded Repository
     * @var AssetRepository
     */
    protected AssetRepository $assetRepository;

    /**
     * Constructor
     * @param string $assetId
     */
    public function __construct(string $assetId)
    {
        $this->assetId=$assetId;
        $this->assetRepository=app(AssetRepository::class);
    }

    /**
     * Execute the job
     */
    public function handle()
    {
        try {
            //update the asset's status
            $this->assetRepository->updateAsset($this->assetId,null,null,AssetStatusEnum::PROCESS->name);
            //get the file info from DB
            $asset=$this->assetRepository->getAsset($this->assetId);
            $key=(string)$asset->ingest['s3']['key'];
            $fileLength=(int)$asset->ingest['file']['length'];
            //get the file from S3 ingest bucket
            $s3Client=self::initS3Client();
            $file = $s3Client->getObject([
                'Bucket' => env("AWS_BUCKET_INGEST"),
                'Key'    => $key,
            ]);
            //check the file length
            if($fileLength==$file["ContentLength"]){
                //update the asset's status
                $this->assetRepository->updateAsset($this->assetId,null,null,AssetStatusEnum::PROCESSING->name);
                //the file length is ok then check if is a video
                $tempUrl = Storage::disk('s3_ingest')->temporaryUrl($key, now()->addSeconds(30));
                if($this->_isVideo($tempUrl)){
                    //get media info
                    $this->_getMediaInfo($key);
                    //create thumbnails
                    $this->_createTile($key);
                    //convert video to HLS
                    $this->_convertVideoToHls($key);
                }else{
                    throw new \Exception("The file is not a video");
                }
            }else{
                throw new \Exception("The file length is wrong");
            }
            $this->assetRepository->updateAsset($this->assetId,null,null,AssetStatusEnum::COMPLETED->name);
        }catch (\Exception $e){
            //on error
            $this->fail($e->getMessage());
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
        $this->assetRepository->updateAsset($this->assetId,null,null,AssetStatusEnum::ERROR->name);
    }

    /**
     * Check if the file is a video
     * @param $url
     * @return bool
     */
    private function _isVideo($url):bool
    {
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
    }

    /**
     * Convert a video to HLS
     * @param string $key
     * @return void
     */
    private function _convertVideoToHls(string $key):void
    {
        //set Presigned Url
        $tempUrl = Storage::disk('s3_ingest')->temporaryUrl($key, now()->addMinutes(120));
        //get video
        $video=FFMpeg::openUrl($tempUrl);
        //get orientation
        $dimensions=$video->getVideoStream()->getDimensions();
        $width = $dimensions->getWidth();
        $height = $dimensions->getHeight();
        //set bitrates and sizes
        $bitrate=$video->getVideoStream()->get("bit_rate")/1000;
        $profiles['SD'] = [
            'bitrate' => (new X264)->setKiloBitrate(1000),
            'size' => ($width > $height) ? 'scale=640:-1' : 'scale=480:-1'
        ];
        if($bitrate>=2000)
            $profiles['HD'] = [
                'bitrate' => (new X264)->setKiloBitrate(2000),
                'size' => ($width > $height) ? 'scale=1280:-1' : 'scale=720:-1'
            ];
        if($bitrate>=4000)
            $profiles['FHD'] = [
                'bitrate' => (new X264)->setKiloBitrate(4000),
                'size' => ($width > $height) ? 'scale=1920:-1' : 'scale=1080:-1'
            ];
        unset($video);
        $transcoder=FFMpeg::openUrl($tempUrl)
            ->exportForHLS()
            ->setSegmentLength(5)
            ->toDisk('s3_media');
        //transcode
        foreach($profiles as $profile){
            $transcoder=$transcoder->addFormat($profile['bitrate'], function($media) use ($profile){
                $media->addFilter($profile['size']);
            });
        }
        $transcoder->save($this->assetId.'/stream/index.m3u8');
    }

    /**
     * Create the tile
     * @param string $key
     * @return void
     */
    private function _createTile(string $key):void
    {
        //set Presigned Url
        $tempUrl = Storage::disk('s3_ingest')->temporaryUrl($key, now()->addMinutes(120));
        //set the transcoder
        $transcoder=FFMpeg::openUrl($tempUrl)
            ->exportTile(function (TileFactory $factory) {
                $factory->interval(10)
                    ->scale(320, 180)
                    ->grid(5, 2)
                    ->generateVTT($this->assetId.'/tile/tile.vtt');
            })
            ->toDisk('s3_media');
        //save
        $transcoder->save($this->assetId.'/tile/tile_%05d.jpg');
    }

    /**
     * Get media info
     * @param string $key
     * @return void
     */
    private function _getMediaInfo(string $key):void
    {
        //set Presigned Url
        $tempUrl = Storage::disk('s3_ingest')->temporaryUrl($key, now()->addMinutes(120));
        //run Media Info Lib
        $output = shell_exec(env("MEDIAINFO_PATH")." --Output=JSON \"$tempUrl\"");
        $data = json_decode($output, true);
        if ($data !== null && isset($data['media']['track'])) {
            //Update asset
            $this->assetRepository->updateAsset($this->assetId,null,null,null,$data['media']['track']);
        }
    }
}