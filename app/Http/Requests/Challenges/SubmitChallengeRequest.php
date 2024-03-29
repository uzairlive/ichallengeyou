<?php

namespace App\Http\Requests\Challenges;

use Illuminate\Foundation\Http\FormRequest;

class SubmitChallengeRequest extends FormRequest
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
        return [
            'file' => ['required', 'mimes:mp4,webm,3gp', 'max:51200‬'],
        ];
    }
}
