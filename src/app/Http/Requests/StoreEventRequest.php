<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules() : array
    {
        return [
            'name' => 'required|string',
            'start_date' => 'required|string',
            'end_date' => 'required|date',
            'type_id' => 'required|numeric',
            'is_home_away'=>'required|boolean',
            'banner' => 'required|string',
            'number_of_teams' => 'required|numeric',
            'is_owner_participate'=>'required|boolean',
            'is_private'=>'required|boolean'
        ];
    }
}