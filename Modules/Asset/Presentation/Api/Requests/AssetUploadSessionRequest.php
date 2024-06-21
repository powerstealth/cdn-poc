<?php

namespace Modules\Asset\Presentation\Api\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Modules\Asset\Domain\Dto\AssetUploadSessionDto;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Asset\Domain\Traits\MediaFileTrait;

class AssetUploadSessionRequest extends FormRequest
{
    use MediaFileTrait;

    /**
     * @return string[]
     */
    public function rules()
    {
        return [
            'file_name' => 'string|required',
            'file_length' => 'integer|required|min:1000000',
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
            "*.integer" => "The param must be a number",
            "*.bool" => "The param must be true or false",
            "file_length.min" => "The file length must be equal or greater than 1M",
            "file_type.in" => "The file must be: ".implode(", ",array_keys(self::getAllowedMediaFiles())),
        ];
    }

    /**
     * DTO Mapper
     * @return AssetUploadSessionDto
     */
    public function data():AssetUploadSessionDto
    {
        return AssetUploadSessionDto::fromRequest($this);
    }
}