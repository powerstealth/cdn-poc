<?php

namespace Modules\Auth\Presentation\Api\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class SignUrlRequest extends FormRequest
{
    /**
     * @return string[]
     */
    public function rules()
    {
        return [
            'url' => 'required|url',
        ];
    }

    /**
     * @param Validator $validator
     */
    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation error',
            'data' => $validator->errors()
        ], 400));
    }

    /**
     * @return string[]
     */
    public function messages()
    {
        return [
            'url.required' => 'The URL field is required.',
            'url.url' => 'The URL must be a valid format.',
        ];
    }
}