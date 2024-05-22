<?php

namespace Modules\Asset\Presentation\Api\Requests;

use Illuminate\Validation\Rule;
use Modules\Asset\Domain\Dto\InfoDto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use function Modules\Ingest\Presentation\Api\Requests\response;

class AssetInfoRequest extends FormRequest
{
    /**
     * @return string[]
     */
    public function rules()
    {
        return [];
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
        return [];
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