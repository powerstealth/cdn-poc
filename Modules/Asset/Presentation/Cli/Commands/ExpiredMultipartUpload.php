<?php

namespace Modules\Asset\Presentation\Cli\Commands;

use Illuminate\Console\Command;
use Modules\Asset\Domain\Services\AssetService;

class ExpiredMultipartUpload extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'upload:purge';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete the expired uploads';

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
        $assetService=app(AssetService::class);
        $assetService->purgeExpiredUploads();
        unset($assetService);
    }
}