<?php
namespace Modules\Asset\Domain\Dto;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Spatie\LaravelData\Data;

class AssetUpdateDto extends Data
{
    /**
     * @param string      $id
     * @param string|null $title
     * @param string|null $description
     * @param array|null  $tags
     * @param bool|null   $published
     */
    #[MapName(SnakeCaseMapper::class)]
    public function __construct(
        public string $id,
        public ?string $title,
        public ?string $description,
        public ?array $tags,
        public ?bool $published,
    ){}

    /**
     * @param Request $request
     * @return static
     */
    public static function fromRequest(Request $request): static
    {
        return new self(
            $request->id,
            $request->title,
            $request->description,
            $request->tags,
            $request->published,
        );
    }
}
