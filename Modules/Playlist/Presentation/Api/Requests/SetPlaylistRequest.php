<?php

namespace Modules\Playlist\Presentation\Api\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Playlist\Domain\Dto\PlaylistCategoryDto;
use Modules\Playlist\Domain\Dto\SetPlaylistDto;

class SetPlaylistRequest extends FormRequest
{
    /**
     * @return string[]
     */
    public function rules()
    {
        return [
            'items' => 'nullable|array',
            'items.*.id' => [
                'required_with:items',
                'exists:assets,_id'
            ],
            'items.*.position' => [
                'required_with:items',
                'numeric',
                'min:1'
            ],
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
            'items.required' => 'The items parameter is required',
            'items.array' => 'The items parameter must be an array',
            'items.*.id.required' => 'The item ID is required',
            'items.*.id.exists' => 'The item ID must exist in the assets',
            'items.*.position.required' => 'The item position is required',
            'items.*.position.numeric' => 'The item position must be a number',
            'items.*.position.min' => 'The item position must be at least 1',
        ];
    }

    /**
     * DTO
     * @return SetPlaylistDto
     */
    public function data():SetPlaylistDto
    {
        return SetPlaylistDto::fromRequest($this);
    }
}