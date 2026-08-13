<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuizResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'quiz_id' => $this->id,
            'difficulty' => (string) $this->difficulty,
            'total_questions' => $this->total_questions,
            'created_at' => $this->created_at,
            'questions' => $this->whenLoaded('questions', function () {
                return $this->questions->map(function ($question) {
                    return [
                        'id' => $question->id,
                        'description' => $question->description,
                        'option_a' => $question->option_a,
                        'option_b' => $question->option_b,
                        'option_c' => $question->option_c,
                        'option_d' => $question->option_d,
                        'correct_answer' => $question->correct_answer,
                        'explanation' => $question->explanation,
                        'level' => (string) $question->level,
                    ];
                });
            }),
        ];
    }
}