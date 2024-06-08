<?php
namespace Modules\Asset\Domain\Dto;

use Spatie\LaravelData\Data;
use Illuminate\Http\Request;
use Modules\Download\Domain\Dto\MapName;
use Modules\Download\Domain\Dto\SnakeCaseMapper;

class InfoDto extends Data
{
    /**
     * Constructor
     * @param string $id
     * @param bool   $json
     */
    #[MapName(SnakeCaseMapper::class)]
    public function __construct(
        public string $id,
        public bool $json
    ){}

    /**
     * @param Request $request
     * @return static
     */
    public static function fromRequest(Request $request): static
    {
        return new self(
            $request->id,
            $request->json == 'json' ? true : false
        );
    }
}
