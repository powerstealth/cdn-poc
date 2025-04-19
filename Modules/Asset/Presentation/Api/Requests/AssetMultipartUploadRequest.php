<?php

namespace Modules\Asset\Presentation\Api\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Modules\Asset\Domain\Traits\MediaFileTrait;
use Modules\Asset\Domain\Dto\AssetMultipartUploadDto;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Asset\Presentation\Api\ValidationRules\TagRule;

class AssetMultipartUploadRequest extends FormRequest
{
    use MediaFileTrait;

    /**
     * @return string[]
     */
    public function rules()
    {
        return [
            'task'          => 'required|string|in:'.$this->_multipartUploadTasks(),
            'file_name'     => 'required_if:task,start|string',
            'file_length'   => 'required_if:task,start|integer|min:1000000',
            'parts'         => 'required_if:task,start|integer|min:2|max:999',
            'asset_id'      => 'required_if:task,complete|string',
            'data'          => 'array',
            'data.tags'     => new TagRule(),
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
            "*.required_if" => "The param is required",
            "*.string" => "The param must be a string",
            "*.integer" => "The param must be a number",
            "*.bool" => "The param must be true or false",
            "file_length.min" => "The file length must be equal or greater than 1M",
            "file_type.in" => "The file must be: ".implode(", ",array_keys(self::getAllowedMediaFiles())),
            "task.in" => "The task must be: ".$this->_multipartUploadTasks(),
            "parts.min" => "The number of parts must be at least 2",
            "parts.max" => "The number of parts must be at most 999",
        ];
    }

    /**
     * DTO Mapper
     * @return AssetMultipartUploadDto
     */
    public function dto():AssetMultipartUploadDto
    {
        return AssetMultipartUploadDto::fromRequest($this);
    }

    /**
     * Multipart upload tasks
     * @return string
     */
    private function _multipartUploadTasks():string
    {
        $tasks=['start','complete'];
        return implode(",",$tasks);
    }
}