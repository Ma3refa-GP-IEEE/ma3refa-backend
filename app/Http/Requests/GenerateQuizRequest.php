<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'difficulty'          => ['required', 'integer', 'in:1,2,3'],
            'number_of_questions' => ['required', 'integer', 'min:1', 'max:50'],
        ];
    }
}