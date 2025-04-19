<?php

namespace Modules\Auth\Presentation\Api\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Auth\Domain\Dto\UserAdminDto;
use Illuminate\Validation\ValidationException;

class UserAdminRequest extends FormRequest
{
    /**
     * @return string[]
     */
    public function rules()
    {
        return [
            'is_admin' => 'bool|required',
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
            "*.bool" => "The param must be true or false",
            "*.required" => "The param is required",
        ];
    }

    /**
     * @return UserAdminDto
     */
    public function dto():UserAdminDto
    {
        return UserAdminDto::fromRequest($this);
    }

}