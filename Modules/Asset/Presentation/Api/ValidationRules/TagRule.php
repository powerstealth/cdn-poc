<?php

namespace Modules\Asset\Presentation\Api\ValidationRules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\Asset\Domain\Enums\AssetScopeEnum;
use Modules\Asset\Domain\Enums\TagGroupsEnum;

class TagRule implements ValidationRule
{

    /**
     * Validation Logic
     * @param string  $attribute
     * @param mixed   $value
     * @param Closure $fail
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $groupTags=array_keys(TagGroupsEnum::getAllItemsAsArray());
        $keys=array_keys($value);
        foreach ($keys as $key) {
            if(!in_array($key,$groupTags)){
                $fail('The :attribute can takes this values: '.implode(", ",$groupTags));
            }
            // Check the right scopes
            if(strtoupper($key)=="SCOPE"){
                //&& !in_array($value['scope'],AssetScopeEnum::getAllValues())
                if(!in_array($value['SCOPE'],AssetScopeEnum::getAllValues()))
                    $fail('The tags.scope can takes this values: '.implode(", ",AssetScopeEnum::getAllValues()));
            }
        }
    }

}
