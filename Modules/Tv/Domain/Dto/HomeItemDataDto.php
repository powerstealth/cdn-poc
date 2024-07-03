<?php
namespace Modules\Tv\Domain\Dto;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Modules\Asset\Domain\Dto\MapName;
use Modules\Asset\Domain\Dto\SnakeCaseMapper;
use Spatie\LaravelData\Data;

class HomeItemDataDto extends Data
{
    /**
     * @param MongoDB\BSON\ObjectId $asset_id
     * @param string $section
     * @param int    $position
     */
    #[MapName(SnakeCaseMapper::class)]
    public function __construct(
        public \MongoDB\BSON\ObjectId $asset_id,
        public string $section,
        public int $position,
    ){}

    /**
     * @param Request $request
     * @return static
     */
    public static function fromRequest(Request $request): static
    {
        return new self(
            $request->asset_id,
            $request->section,
            $request->position
        );
    }
}
