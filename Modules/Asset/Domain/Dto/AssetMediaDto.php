<?php
namespace Modules\Asset\Domain\Dto;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Spatie\LaravelData\Data;

class AssetMediaDto extends Data
{
    /**
     * Construct
     * @param string $hls
     * @param string $key_frame
     */
    #[MapName(SnakeCaseMapper::class)]
    public function __construct(
        public string $hls,
        public string $key_frame,
    ){}

    /**
     * @param Request $request
     * @return static
     */
    public static function fromRequest(Request $request): static
    {
        return new self(
            $request->hls,
            $request->key_frame
        );
    }
}
