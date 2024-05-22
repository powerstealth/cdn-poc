<?php

namespace Modules\Asset\Presentation\Api\Requests;

use Illuminate\Validation\Rule;
use Modules\Asset\Domain\Dto\ListDto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class AssetListRequest extends FormRequest
{
    /**
     * @return string[]
     */
    public function rules()
    {
        return [
            'page' => 'min:1',
            'limit' => 'min:1|max:50',
            'filters' => 'array',
            'sort_field' => Rule::in(['id','name','total_jobs','pending_jobs','failed_jobs','cancelled_at','created_at','finished_at']),
            'sort_order' => Rule::in(['asc','desc']),
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
            "page.min" => "The param must take a value equal or more than 1",
            "limit.min" => "The param must take a value equal or more than 1",
            "limit.max" => "The param must take a value equal or less than 50",
            "sort_order.in" => "The param can take asc or desc values",
        ];
    }

    /**
     * @return ListDto
     */
    public function data():ListDto
    {
        return ListDto::fromRequest($this);
    }

}