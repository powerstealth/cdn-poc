<?php

namespace Modules\Asset\Presentation\Cli\Commands;

use Illuminate\Console\Command;
use Modules\Asset\Domain\Services\AssetService;

class S3Cors extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 's3:cors {bucket?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add cors to a S3 bucket';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //Set the bucket
        $bucket = $this->argument('bucket');
        //Init the service
        $assetService=app(AssetService::class);
        //Set the CORS
        $assetService->SetCorsToS3MediaBucket($bucket);
        unset($assetService);
    }
}