<?php
namespace Modules\Asset\Domain\Enums;

use Modules\Asset\Domain\Contracts\EnumInterface as EnumInterface;

enum TagGroupsEnum:string implements EnumInterface{

    case SCOPE = "Scope";
    case CLYUP_TV = "ClyUp Tv";

    /**
     * Return all names
     * @return array
     */
    public static function getAllNames(): array
    {
        return array_column(self::cases(), 'name');
    }

    /**
     * Return all values
     * @return array
     */
    public static function getAllValues(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Return all values
     * @return array
     */
    public static function getAllItemsAsArray(): array
    {
        return array_combine(self::getAllNames(),self::getAllValues());
    }
}