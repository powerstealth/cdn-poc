<?php
namespace Modules\Asset\Domain\Dto;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Spatie\LaravelData\Data;

class AssetUploadSessionDto extends Data
{
    /**
     * Constructor
     * @param string $file_name
     * @param int    $file_length
     */
    #[MapName(SnakeCaseMapper::class)]
    public function __construct(
        public string $file_name,
        public int $file_length,
    ){}

    /**
     * @param Request $request
     * @return static
     */
    public static function fromRequest(Request $request): static
    {
        return new self(
            $request->file_name,
            $request->file_length,
        );
    }
}