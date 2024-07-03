<?php
namespace Modules\Tv\Domain\Dto;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Modules\Asset\Domain\Dto\MapName;
use Modules\Asset\Domain\Dto\SnakeCaseMapper;
use Spatie\LaravelData\Data;

class SetHomeDto extends Data
{
    /**
     * @param array $items
     */
    #[MapName(SnakeCaseMapper::class)]
    public function __construct(
        public array $items,
    ){}

    /**
     * @param Request $request
     * @return static
     */
    public static function fromRequest(Request $request): static
    {
        return new self(
            $request->items
        );
    }
}
