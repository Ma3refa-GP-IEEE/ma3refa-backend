<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuizDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $userAnswers = $this->userAnswers->keyBy('question_id');

        return [
            'quiz_id'         => $this->id,
            'subcategory'     => $this->subcategory->name ?? null,
            'difficulty'      => (string) $this->difficulty,
            'score'           => $this->score,
            'total_questions' => $this->total_questions,
            'created_at'      => $this->created_at,
            'results'         => $this->questions->map(function ($question) use ($userAnswers) {
                $userAnswer = $userAnswers->get($question->id);
                $selected   = $userAnswer ? $userAnswer->selected_answer : null;

                return [
                    'question_id'     => $question->id,
                    'description'     => $question->description,
                    'option_a'        => $question->option_a,
                    'option_b'        => $question->option_b,
                    'option_c'        => $question->option_c,
                    'option_d'        => $question->option_d,
                    'selected_answer' => $selected,
                    'correct_answer'  => $question->correct_answer,
                    'is_correct'      => $selected !== null && strtolower($selected) === strtolower($question->correct_answer),
                    'explanation'     => $question->explanation,
                ];
            }),
        ];
    }
}