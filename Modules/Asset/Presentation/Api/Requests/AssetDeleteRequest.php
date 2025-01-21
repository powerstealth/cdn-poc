<?php

namespace Modules\Asset\Presentation\Api\Requests;

use Modules\Asset\Domain\Dto\AssetDeleteDto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class AssetDeleteRequest extends FormRequest
{
    /**
     * @return string[]
     */
    public function rules()
    {
        return [

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

        ];
    }

    /**
     * DTO Mapper
     * @return AssetDeleteDto
     */
    public function dto():AssetDeleteDto
    {
        return AssetDeleteDto::fromRequest($this);
    }

}