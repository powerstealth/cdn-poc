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
            //get the file info
            $asset=$this->assetRepository->getAsset($this->assetId);
            $key=(string)$asset->ingest['s3']['key'];
            $fileLength=(int)$asset->ingest['file']['length'];
            //get the file from S3 ingest bucket
            $s3Client=self::initS3Client();
            $file = $s3Client->getObject([
                'Bucket' => env("AWS_BUCKET"),
                'Key'    => $key,
            ]);
            //the file length is ok
            if($fileLength==$file["ContentLength"]){
                dd(1);
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
}