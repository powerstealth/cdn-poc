<?php
namespace Modules\Asset\Domain\Dto;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Spatie\LaravelData\Data;

class AssetMultipartUploadDto extends Data
{
    /**
     * Constructor
     * @param string      $task
     * @param string|null $file_name
     * @param int|null    $file_length
     * @param bool|null   $scope_clyup_tv
     * @param bool|null   $scope_clyup_front_store
     * @param int|null    $parts
     * @param string|null $asset_id
     * @param array|null  $data
     */
    #[MapName(SnakeCaseMapper::class)]
    public function __construct(
        public string $task,
        public ?string $file_name,
        public ?int $file_length,
        public ?bool $scope_clyup_tv,
        public ?bool $scope_clyup_front_store,
        public ?int $parts,
        public ?string $asset_id,
        public ?array $data
    ){}

    /**
     * @param Request $request
     * @return static
     */
    public static function fromRequest(Request $request): static
    {
        return new self(
            $request->task,
            $request->file_name,
            $request->file_length,
            $request->scope_clyup_tv,
            $request->scope_clyup_front_store,
            $request->parts,
            $request->asset_id,
            $request->data,
        );
    }
}