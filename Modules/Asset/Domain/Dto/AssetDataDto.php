<?php
namespace Modules\Asset\Domain\Dto;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Spatie\LaravelData\Data;

class AssetDataDto extends Data
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
        public ?array $tags,
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
            $request->tags
        );
    }
}
