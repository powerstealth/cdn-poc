<?php
namespace Modules\Asset\Domain\Jobs;

use Aws\S3\S3Client;
use Illuminate\Support\Str;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Modules\Asset\Domain\Traits\S3Trait;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Modules\Asset\Domain\Enums\AssetStatusEnum;
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
                //the file length is ok then check if is a video
                $tempUrl = Storage::disk('s3_ingest')->temporaryUrl($key, now()->addSeconds(30));
                if($this->_isVideo($tempUrl)){
                    $this->_convertVideoToHls();
                }else{
                    throw new \Exception("The file is not a video");
                }
            }else{
                throw new \Exception("The file length is wrong");
            }
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
        $this->assetRepository->updateAsset($this->assetId,null,null,AssetStatusEnum::ERROR);
    }

    /**
     * Check if the file is a video
     * @param $file
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

    private function _convertVideoToHls():void
    {

    }
}