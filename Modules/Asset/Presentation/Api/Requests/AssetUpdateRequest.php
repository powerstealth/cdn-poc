<?php

namespace Modules\Asset\Presentation\Api\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Asset\Domain\Dto\AssetUpdateDto;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Asset\Domain\Enums\AssetVerificationEnum;
use Modules\Asset\Presentation\Api\ValidationRules\TagRule;

class AssetUpdateRequest extends FormRequest
{
    /**
     * @return string[]
     */
    public function rules()
    {
        return [
            'title' => 'string|max:255',
            'description' => 'string',
            'tags' => new TagRule(),
            'published' => 'bool',
            'verification' => 'string|in:'.implode(",",AssetVerificationEnum::getAllNames())
        ];
    }

    /**
     * @param Validator $validator
     */
    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Error',
            'data' => $validator->errors()
        ],400));
    }

    /**
     * @return string[]
     */
    public function messages(){
        return [
            "*.required" => "The param is required",
            "*.string" => "The param must be a string",
            "*.max" => "The param length must be at lease 255 characters",
            "*.boolean" => "The param can take true or false",
            "*.array" => "The param must be an array",
            "verification.in" => "The param must take one of the following values: " . implode(",",AssetVerificationEnum::getAllNames()),
        ];
    }

    /**
     * DTO Mapper
     * @return AssetUpdateDto
     */
    public function dto():AssetUpdateDto
    {
        return AssetUpdateDto::fromRequest($this);
    }

}