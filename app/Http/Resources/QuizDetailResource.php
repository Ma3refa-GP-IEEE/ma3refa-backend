<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuizDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // تجهيز Map لإجابات اليوزر على الكويز ده لسهولة الوصول إليها
        $userAnswers = $this->userAnswers->keyBy('question_id');

        return [
            'quiz_id'         => $this->id,
            'subcategory'     => $this->subcategory->name ?? null,
            'difficulty'      => $this->difficulty,
            'score'           => $this->score,
            'total_questions' => $this->total_questions,
            'created_at'      => $this->created_at,
            'results'         => $this->questions->map(function ($question) use ($userAnswers) {
                $userAnswer = $userAnswers->get($question->id);

                return [
                    'question_id'     => $question->id,
                    'description'     => $question->description,
                    'option_a'        => $question->option_a,
                    'option_b'        => $question->option_b,
                    'option_c'        => $question->option_c,
                    'option_d'        => $question->option_d,
                    'selected_answer' => $userAnswer ? $userAnswer->selected_answer : null,
                    'correct_answer'  => $question->correct_answer,
                    'is_correct'      => $userAnswer ? (bool) $userAnswer->is_correct : false,
                    'explanation'     => $question->explanation,
                ];
            }),
        ];
    }
}
