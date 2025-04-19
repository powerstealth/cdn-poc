<?php
namespace Modules\Auth\Domain\Dto;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Modules\Asset\Domain\Dto\MapName;
use Modules\Asset\Domain\Dto\SnakeCaseMapper;
use Spatie\LaravelData\Data;

class UserAdminDto extends Data
{
    /**
     * @param bool $is_admin
     */
    #[MapName(SnakeCaseMapper::class)]
    public function __construct(
        public bool $is_admin,
    ){}

    /**
     * @param Request $request
     * @return static
     */
    public static function fromRequest(Request $request): static
    {
        return new self(
            $request->is_admin,
        );
    }
}
