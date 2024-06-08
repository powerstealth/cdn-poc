<?php

namespace Modules\Asset\Presentation\Api\Requests;

use Modules\Asset\Domain\Dto\InfoDto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class AssetInfoRequest extends FormRequest
{
    /**
     * @return string[]
     */
    public function rules()
    {
        return [
            'json' => 'nullable|in:json',
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
            "json.in" => "The param must take json or be null",
        ];
    }

    /**
     * DTO Mapper
     * @return InfoDto
     */
    public function data():InfoDto
    {
        return InfoDto::fromRequest($this);
    }

}