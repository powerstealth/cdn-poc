<?php

namespace Modules\System\Domain\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class SystemService
{

    /**
     * Constructor
     */
    public function __construct(){
    }

    /**
     * Ping
     * @return array
     */
    public function ping():array{
        return [
            "success"=>true,
            "message"=>"Application up",
            "data"=>[
                "worker"=>env("WORKER_ID")
            ],
            "error"=>"",
            "response_status"=>200
        ];
    }

    /**
     * Version command
     * @return array
     */
    public function getVersion():array{
        return [
            "success"=>true,
            "message"=>"",
            "data"=>[
                "version"=>config('app.version')
            ],
            "error"=>"",
            "response_status"=>200
        ];
    }

}