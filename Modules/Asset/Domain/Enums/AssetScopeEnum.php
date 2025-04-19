<?php
namespace Modules\Asset\Domain\Enums;

use Modules\Asset\Domain\Contracts\EnumInterface as EnumInterface;

enum AssetScopeEnum:string implements EnumInterface{

    case CLYUP_SELECTED_FOR_TV  = "CLYUP_SELECTED_FOR_TV";
    case CLYUP_TV               = "CLYUP_TV";
    case CLYUP_STOREFRONT       = "CLYUP_STOREFRONT";

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