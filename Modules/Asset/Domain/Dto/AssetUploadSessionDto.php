<?php
namespace Modules\Asset\Domain\Dto;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Spatie\LaravelData\Data;

class AssetUploadSessionDto extends Data
{
    /**
     * Constructor
     * @param int    $file_length
     * @param string $file_type
     * @param bool   $scope_clyup_tv
     * @param bool   $scope_clyup_front_store
     */
    #[MapName(SnakeCaseMapper::class)]
    public function __construct(
        public int $file_length,
        public string $file_type,
        public bool $scope_clyup_tv,
        public bool $scope_clyup_front_store,
    ){}

    /**
     * @param Request $request
     * @return static
     */
    public static function fromRequest(Request $request): static
    {
        return new self(
            $request->file_length,
            $request->file_type,
            $request->scope_clyup_tv,
            $request->scope_clyup_front_store,
        );
    }
}