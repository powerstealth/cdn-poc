<?php

namespace Modules\Auth\Presentation\Cli\Commands;

use Illuminate\Console\Command;
use Modules\Auth\Domain\Services\AuthService;

class SetUserRole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:set-role {--role=} {--user-id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set role to a user';

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
        $authService=app(AuthService::class);
        //check params
        if($this->option('role')==null)
            die("ERROR: set the role\n");
        if($this->option('role')!="admin")
            die("ERROR: the role can takes only admin\n");
        if($this->option('user-id')==null)
            die("ERROR: set the user\n");
        //run command
        $response=$authService->setUserRole($this->option('role'),$this->option('user-id'));
        if($response)
            die("The user has been updated\n");
        else
            die("Something wrong\n");
    }
}