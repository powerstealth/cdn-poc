<?php
namespace Modules\Asset\Domain\Dto;

use Spatie\LaravelData\Data;
use Illuminate\Http\Request;
use Modules\Download\Domain\Dto\MapName;
use Modules\Download\Domain\Dto\SnakeCaseMapper;

class DeleteDto extends Data
{
    /**
     * Constructor
     * @param string $id
     */
    #[MapName(SnakeCaseMapper::class)]
    public function __construct(
        public string $id,
    ){}

    /**
     * @param Request $request
     * @return static
     */
    public static function fromRequest(Request $request): static
    {
        return new self(
            $request->id,
        );
    }
}
