<?php

namespace Modules\Asset\Presentation\Api\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class AssetPosterRequest extends FormRequest
{
    /**
     * @return string[]
     */
    public function rules()
    {
        return [
            'poster' => 'required|file',
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
            "*.file" => "The param must be a file",
        ];
    }

}