<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class GenerateQuizRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'subcategory_id'      => ['required', 'integer', 'exists:subcategories,id'],
            'difficulty'          => ['required', 'string', Rule::in(['easy', 'medium', 'hard', 'Easy', 'Medium', 'Hard'])],
            'number_of_questions' => ['required', 'integer', 'min:1'],
        ];
    }
}