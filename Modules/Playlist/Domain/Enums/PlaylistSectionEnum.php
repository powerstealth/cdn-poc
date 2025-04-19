<?php
namespace Modules\Playlist\Domain\Enums;

use Modules\Playlist\Domain\Contracts\EnumInterface as EnumInterface;

enum PlaylistSectionEnum:string implements EnumInterface{

    case VIRTUAL_SHOW  = "virtual-show";

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