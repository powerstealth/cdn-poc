<?php

namespace Modules\Asset\Presentation\Cli\Commands;

use Illuminate\Console\Command;
use Modules\Asset\Domain\Services\AssetService;

class PurgeUploads extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'upload:purge {--wipe} {--expired-uploads}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete the files into the ingest folder';

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
        //Set the service
        $assetService=app(AssetService::class);
        //actions
        if($this->option('expired-uploads'))
            $assetService->purgeExpiredUploads();
        elseif($this->option('wipe'))
            $assetService->wipeUploads();
        else
            echo("Set --wipe or --expired-uploads\n");
        unset($assetService);
    }
}