<?php

namespace App\Http\Controllers;

use App\Http\Resources\QuizResource;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Subcategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class QuizController extends Controller
{
    public function generate(Request $request)
    {
        $request->validate([
            'subcategory_id' => ['required', 'integer', 'exists:subcategories,id'],
            'difficulty' => ['required', 'integer', 'in:1,2,3'],
            'number_of_questions' => ['required', 'integer', 'min:1', 'max:50']
        ]);

        $subcategory = Subcategory::with(['category', 'allowedTopics'])->findOrFail($request->subcategory_id);

        $difficulty_map = [1 => 'easy', 2 => 'medium', 3 => 'hard'];
        $difficulty_text = $difficulty_map[$request->difficulty];

        $payload = [
            "category" => $subcategory->category->name,
            "sub_category" => $subcategory->name,
            "difficulty" => $difficulty_text,
            "allowed_topics" => $subcategory->allowedTopics->pluck('topic_name'),
            "num_questions" => $request->number_of_questions,
        ];

        $response = $this->callAiService($payload);
        $mapped_questions = $this->mapAiResponseToQuestions($response, $subcategory);

        $quiz = $this->storeQuizWithQuestions($mapped_questions, $subcategory, $request->difficulty, $request->number_of_questions);

        $quiz->load('questions');

        return new QuizResource($quiz);
    }

    public function callAiService(array $payload)
    {
        $response = Http::post('https://ma3refa-ai-engine-546d.vercel.app/api/generate-quiz', $payload);

        return $response->json();
    }

    public function mapAiResponseToQuestions($response, $subcategory)
    {
        $index_to_letter = ['a', 'b', 'c', 'd'];
        $mapped_questions = [];
        $allowedTopics = $subcategory->allowedTopics;

        foreach ($response['questions'] as $question) {
            $correct_letter = $index_to_letter[$question['correct_index']] ?? 'a';
            $aiTopicName = trim($question['topic'] ?? '');

            $matchedTopic = $allowedTopics->first(function ($topic) use ($aiTopicName) {
                return strtolower(trim($topic->topic_name)) === strtolower($aiTopicName);
            }) ?? $allowedTopics->first();

            $mapped_questions[] = [
                'description'      => $question['question'],
                'option_a'         => $question['options'][0],
                'option_b'         => $question['options'][1],
                'option_c'         => $question['options'][2],
                'option_d'         => $question['options'][3],
                'correct_answer'   => $correct_letter,
                'explanation'      => $question['explanation'],
                'allowed_topic_id' => $matchedTopic ? $matchedTopic->id : null,
            ];
        }

        return $mapped_questions;
    }

    public function storeQuizWithQuestions($mapped_questions, $subcategory, $difficulty, $number_of_questions)
    {
        return DB::transaction(function () use ($mapped_questions, $subcategory, $difficulty, $number_of_questions) {
            $quiz = Quiz::create([
                'user_id' => Auth::id(),
                'subcategory_id' => $subcategory->id,
                'difficulty' => $difficulty,
                'score' => 0,
                'total_questions' => $number_of_questions,
                'created_at' => now(),
            ]);

            foreach ($mapped_questions as $index => $question) {
                $questionRecord = Question::firstOrCreate(
                    ['description' => $question['description']],
                    [
                        'level' => $difficulty,
                        'option_a' => $question['option_a'],
                        'option_b' => $question['option_b'],
                        'option_c' => $question['option_c'],
                        'option_d' => $question['option_d'],
                        'correct_answer' => $question['correct_answer'],
                        'explanation' => $question['explanation'],
                        'allowed_topic_id' => $question['allowed_topic_id'],
                        'created_at' => now(),
                    ]
                );

                $quiz->questions()->attach($questionRecord->id, ['question_order' => $index + 1]);
            }

            return $quiz;
        });
    }

    public function show() {}
    public function finish() {}
}
