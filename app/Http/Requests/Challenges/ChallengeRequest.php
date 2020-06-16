<?php

namespace App\Http\Requests\Challenges;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChallengeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'title' => ['bail', 'required', 'unique:challenges', 'string', 'max:75', 'min:3'],
            'description' => ['bail', 'required', 'max:500', 'min:200'],
            'start_time' => ['required', 'date_format:Y-m-d H:i', 'after:'.date(DATE_ATOM, time() + (5 * 60 * 60))],
            'duration_days' => ['required', 'integer', 'min:0'],
            'duration_hours' => ['required', 'integer', 'min:0', 'max:23'],
            'duration_minutes' => ['required', 'integer', 'min:0', 'max:59'],
            'location' => ['required', 'string', 'max:75'],
            'amount' => ['required', 'numeric', 'min:1'],
        ];
        switch ($this->method()) {
            case 'POST': {
                $rules['terms_accepted'] = ['required', Rule::in(['true'])];
                $rules['file'] = ['required', 'mimes:jpg,jpeg,png,mp4,webm'];
            }
            case 'PUT' || 'PATCH': {
                $rules['file'] = ['mimes:jpg,jpeg,png,mp4,webm', 'max:51200‬'];
            }
            default:
                break;
        }
        return $rules;
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'title.unique' => 'Challenge with the provided title already exists.',
            'start_time.after' => 'Challenge start time must be greater than the current time.'
        ];
    }


}
