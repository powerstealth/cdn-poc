<?php
namespace Modules\Playlist\Domain\Dto;

use Illuminate\Http\Request;
use Modules\Asset\Domain\Dto\MapName;
use Modules\Asset\Domain\Dto\SnakeCaseMapper;
use Spatie\LaravelData\Data;

class PlaylistStreamDto extends Data
{
    /**
     * Playlist DTO
     * @param string|null $title
     * @param string|null $description
     * @param string      $streaming_url
     * @param string      $poster_hd
     * @param string      $poster_sd
     * @param string      $poster_thumbnail
     */
    #[MapName(SnakeCaseMapper::class)]
    public function __construct(
        public ?string $title,
        public ?string $description,
        public string $streaming_url,
        public string $poster_hd,
        public string $poster_sd,
        public string $poster_thumbnail,
    ){}

    /**
     * @param Request $request
     * @return static
     */
    public static function fromRequest(Request $request): static
    {
        return new self(
            $request->title,
            $request->description,
            $request->streaming_url,
            $request->poster_hd,
            $request->poster_sd,
            $request->poster_thumbnail,
        );
    }
}
