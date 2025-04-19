<?php

namespace Modules\Playlist\Domain\Contracts;

interface EnumInterface
{
    public static function getAllNames(): array;
    public static function getAllValues(): array;
    public static function getAllItemsAsArray(): array;
}