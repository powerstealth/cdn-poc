<?php

namespace Modules\Asset\Presentation\Cli\Commands;

use Illuminate\Console\Command;
use Modules\Asset\Domain\Services\AssetService;

class PurgeDeletedAssets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'assets:purge';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Purge the deleted assets';

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
        $assetService->purgeDeletedAssets();
        unset($assetService);
    }
}