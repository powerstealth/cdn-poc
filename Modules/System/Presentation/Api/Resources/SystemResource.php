<?php

namespace Modules\System\Presentation\Api\Resources;

use Illuminate\Http\Request;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;

class SystemResource extends Data
{
    /**
     * @param bool       $success
     * @param string     $message
     * @param array|null $data
     * @param string     $error
     * @param int        $responseStatus
     */
    public function __construct(
        public bool $success,
        public string $message,
        public array|null $data,
        public string $error,
        #[MapOutputName('response_status'),MapInputName('response_status')]
        public int $responseStatus=200
    ){}

}