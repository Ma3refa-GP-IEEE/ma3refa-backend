<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FinishQuizRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subcategory_id'            => ['required', 'integer', 'exists:subcategories,id'],
            'score'                     => ['required', 'integer', 'min:0', 'max:50'],
            'answers'                   => ['required', 'array'],
            'answers.*.question_id'     => ['required', 'integer', 'exists:questions,id'],
            'answers.*.selected_answer' => ['nullable', 'string', 'in:a,b,c,d,A,B,C,D'],
            'answers.*.is_correct'      => ['required', 'boolean'],
        ];
    }
}