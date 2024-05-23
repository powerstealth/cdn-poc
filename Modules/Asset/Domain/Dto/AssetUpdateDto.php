<?php
namespace Modules\Asset\Domain\Dto;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Spatie\LaravelData\Data;

class AssetUpdateDto extends Data
{
    /**
     * Construct
     * @param string|null $title
     * @param string|null $description
     */
    #[MapName(SnakeCaseMapper::class)]
    public function __construct(
        public ?string $title,
        public ?string $description,
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
        );
    }
}
