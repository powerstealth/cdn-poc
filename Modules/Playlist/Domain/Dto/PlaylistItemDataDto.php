<?php
namespace Modules\Playlist\Domain\Dto;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Spatie\LaravelData\Data;
use Modules\Asset\Domain\Dto\MapName;
use Modules\Asset\Domain\Dto\SnakeCaseMapper;

class PlaylistItemDataDto extends Data
{
    /**
     * @param \MongoDB\BSON\ObjectId $asset_id
     * @param string                 $section
     * @param int                    $position
     * @param \MongoDB\BSON\ObjectId $created_by
     */
    #[MapName(SnakeCaseMapper::class)]
    public function __construct(
        public \MongoDB\BSON\ObjectId $asset_id,
        public string $section,
        public int $position,
        public \MongoDB\BSON\ObjectId $created_by
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
            $request->position,
            $request->created_by
        );
    }
}
