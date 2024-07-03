<?php

namespace Modules\Tv\Domain\Contracts;

interface HomeRepositoryInterface
{
    public function getHomeList(string $section): array|\Exception;
    public function setHomeList(array $items, string $section): bool|\Exception;
}