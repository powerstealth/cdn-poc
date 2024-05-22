<?php
namespace Modules\Asset\Domain\Dto;

use Spatie\LaravelData\Data;
use Illuminate\Http\Request;
use Modules\Download\Domain\Dto\MapName;
use Modules\Download\Domain\Dto\SnakeCaseMapper;
use Spatie\LaravelData\Trasformers\DateTimeInterfaceTransformer;

class PaginationDto extends Data
{
    /**
     * Constructor
     * @param int  $current_page
     * @param int  $total_pages
     * @param int  $total_items
     * @param int  $page_items
     * @param bool $next
     * @param bool $prev
     */
    #[MapName(SnakeCaseMapper::class)]
    public function __construct(
        public int $current_page,
        public int $total_pages,
        public int $total_items,
        public int $page_items,
        public bool $next,
        public bool $prev,
    ){}

    /**
     * @param Request $request
     * @return static
     */
    public static function fromRequest(Request $request): static
    {
        return new self(
            $request->current_page,
            $request->total_pages,
            $request->total_items,
            $request->page_items,
            $request->next,
            $request->prev
        );
    }
}