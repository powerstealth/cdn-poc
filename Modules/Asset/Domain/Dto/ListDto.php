<?php
namespace Modules\Asset\Domain\Dto;

use Spatie\LaravelData\Data;
use Illuminate\Http\Request;
use Modules\Download\Domain\Dto\MapName;
use Modules\Download\Domain\Dto\SnakeCaseMapper;
use Spatie\LaravelData\Trasformers\DateTimeInterfaceTransformer;

class ListDto extends Data
{
    /**
     * Constructor
     * @param int|string|null $id
     * @param int|null        $page
     * @param int|null        $limit
     * @param string|null     $sortField
     * @param string|null     $sortOrder
     * @param array|null      $filters
     * @param string|null     $search
     * @param bool            $setPagination
     */
    #[MapName(SnakeCaseMapper::class)]
    public function __construct(
        public int|string|null $id,
        public ?int $page,
        public ?int $limit,
        public ?string $sortField,
        public ?string $sortOrder,
        public ?array $filters,
        public ?string $search,
        public bool $setPagination,
    ){}

    /**
     * @param Request $request
     * @return static
     */
    public static function fromRequest(Request $request): static
    {
        return new self(
            $request->id ?? null,
            ($request->page === null || $request->page <= 0 ? 1 : $request->page),
            ($request->limit === null || $request->limit <= 0 || $request->limit >= 50 ? 15 : $request->limit),
            ($request->sort_field === null ? "id" : $request->sort_field),
            ($request->sort_order === null ? "asc" : $request->sort_order),
            ($request->filters === null || count($request->filters)==0 ? [] : $request->filters),
            $request->search,
            ($request->set_pagination === null ? true : (bool)$request->set_pagination)
        );
    }

}