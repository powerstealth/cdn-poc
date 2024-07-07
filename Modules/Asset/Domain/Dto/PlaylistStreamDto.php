<?php
namespace Modules\Asset\Domain\Dto;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Spatie\LaravelData\Data;

class PlaylistStreamDto extends Data
{
    /**
     * @param string|null $title
     * @param string|null $description
     * @param array|null  $tags
     */
    #[MapName(SnakeCaseMapper::class)]
    public function __construct(
        public ?string $title,
        public ?string $description,
        public string $streaming_url,
        public string $poster,
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
            $request->poster,
        );
    }
}
