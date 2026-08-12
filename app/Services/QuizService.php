<?php

namespace App\Services;

use App\Models\Question;
use App\Models\Quiz;
use App\Models\Subcategory;
use App\Models\UserAnswer;
use App\Models\UserSubcategoryPoint;
use App\Models\Streak;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class QuizService
{
    public function generateQuiz(Subcategory $subcategory, int $difficulty, int $numberOfQuestions, int $userId): Quiz
    {
        $cachedQuestions = $this->getCachedQuestions($subcategory, $difficulty, $numberOfQuestions, $userId);

        if ($cachedQuestions->count() >= $numberOfQuestions) {
            $quiz = $this->storeQuizFromCache($cachedQuestions, $subcategory->id, $difficulty);
        } else {
            $difficultyMap = [1 => 'easy', 2 => 'medium', 3 => 'hard'];

            $payload = [
                "category"       => $subcategory->category->name,
                "sub_category"    => $subcategory->name,
                "difficulty"     => $difficultyMap[$difficulty],
                "allowed_topics" => $subcategory->allowedTopics->pluck('topic_name'),
                "num_questions"  => $numberOfQuestions,
            ];

            $aiResponse = $this->callAiService($payload);

            if (!isset($aiResponse['questions']) || !is_array($aiResponse['questions'])) {
                throw new \Exception('Failed to generate questions from AI service.', 502);
            }

            $mappedQuestions = $this->mapAiResponseToQuestions($aiResponse, $subcategory);
            $quiz = $this->storeQuizWithQuestions($mappedQuestions, $subcategory, $difficulty, $numberOfQuestions, $userId);
        }

        return $quiz->load('questions');
    }

    public function finishQuiz(Quiz $quiz, array $data, int $userId): void
    {
        // منع الـ Score Spoofing وحسابه أمنياً من الـ Backend
        $calculatedScore = collect($data['answers'])->where('is_correct', true)->count();

        DB::transaction(function () use ($quiz, $data, $userId, $calculatedScore) {
            foreach ($data['answers'] as $answer) {
                UserAnswer::create([
                    'user_id'         => $userId,
                    'quiz_id'         => $quiz->id,
                    'question_id'     => $answer['question_id'],
                    'selected_answer' => strtolower($answer['selected_answer']),
                    'is_correct'      => $answer['is_correct'],
                    'answered_at'     => now(),
                ]);
            }

            $quiz->update([
                'score'       => $calculatedScore,
                'finished_at' => now(),
            ]);

            $userPoint = UserSubcategoryPoint::firstOrCreate(
                [
                    'user_id'        => $userId,
                    'subcategory_id' => $quiz->subcategory_id,
                ],
                ['total_points' => 0]
            );
            $userPoint->increment('total_points', $calculatedScore);

            $this->updateUserStreak($userId);
        });
    }

    private function getCachedQuestions(Subcategory $subcategory, int $difficulty, int $limit, int $userId)
    {
        return Question::whereIn('allowed_topic_id', $subcategory->allowedTopics->pluck('id'))
            ->where('level', $difficulty)
            ->whereDoesntHave('userAnswers', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }

    private function storeQuizFromCache($questions, int $subcategoryId, int $difficulty): Quiz
    {
        return DB::transaction(function () use ($questions, $subcategoryId, $difficulty) {
            $quiz = Quiz::create([
                'user_id'         => Auth::id(),
                'subcategory_id'  => $subcategoryId,
                'difficulty'      => $difficulty,
                'score'           => 0,
                'total_questions' => $questions->count(),
                'created_at'      => now(),
            ]);

            foreach ($questions as $index => $question) {
                $quiz->questions()->attach($question->id, ['question_order' => $index + 1]);
            }

            return $quiz;
        });
    }

    private function callAiService(array $payload): array
    {
        $response = Http::timeout(15)->post('https://ma3refa-ai-engine-546d.vercel.app/api/generate-quiz', $payload);
        return $response->json();
    }

    private function mapAiResponseToQuestions(array $response, Subcategory $subcategory): array
    {
        $indexToLetter = ['a', 'b', 'c', 'd'];
        $mappedQuestions = [];
        $allowedTopics = $subcategory->allowedTopics;

        foreach ($response['questions'] as $question) {
            $correctLetter = $indexToLetter[$question['correct_index'] ?? 0] ?? 'a';
            $aiTopicName = trim($question['topic'] ?? '');

            $matchedTopic = $allowedTopics->first(function ($topic) use ($aiTopicName) {
                return strtolower(trim($topic->topic_name)) === strtolower($aiTopicName);
            }) ?? $allowedTopics->first();

            $options = $question['options'] ?? ['', '', '', ''];

            $mappedQuestions[] = [
                'description'      => $question['question'] ?? '',
                'option_a'         => $options[0] ?? '',
                'option_b'         => $options[1] ?? '',
                'option_c'         => $options[2] ?? '',
                'option_d'         => $options[3] ?? '',
                'correct_answer'   => $correctLetter,
                'explanation'      => $question['explanation'] ?? '',
                'allowed_topic_id' => $matchedTopic ? $matchedTopic->id : null,
            ];
        }

        return $mappedQuestions;
    }

    private function storeQuizWithQuestions(array $mappedQuestions, Subcategory $subcategory, int $difficulty, int $numberOfQuestions, int $userId): Quiz
    {
        return DB::transaction(function () use ($mappedQuestions, $subcategory, $difficulty, $numberOfQuestions, $userId) {
            $quiz = Quiz::create([
                'user_id'         => $userId,
                'subcategory_id'  => $subcategory->id,
                'difficulty'      => $difficulty,
                'score'           => 0,
                'total_questions' => $numberOfQuestions,
                'created_at'      => now(),
            ]);

            foreach ($mappedQuestions as $index => $question) {
                $questionRecord = Question::firstOrCreate(
                    ['description' => $question['description']],
                    [
                        'level'            => $difficulty,
                        'option_a'         => $question['option_a'],
                        'option_b'         => $question['option_b'],
                        'option_c'         => $question['option_c'],
                        'option_d'         => $question['option_d'],
                        'correct_answer'   => $question['correct_answer'],
                        'explanation'      => $question['explanation'],
                        'allowed_topic_id' => $question['allowed_topic_id'],
                        'created_at'       => now(),
                    ]
                );

                $quiz->questions()->attach($questionRecord->id, ['question_order' => $index + 1]);
            }

            return $quiz;
        });
    }

    private function updateUserStreak(int $userId): void
    {
        $today = Carbon::today();

        $streak = Streak::firstOrCreate(
            ['user_id' => $userId],
            ['current_streak' => 1, 'last_activity_date' => $today]
        );

        if (!$streak->wasRecentlyCreated) {
            $lastActivity = Carbon::parse($streak->last_activity_date)->startOfDay();

            if ($lastActivity->isYesterday()) {
                $streak->increment('current_streak');
                $streak->update(['last_activity_date' => $today]);
            } elseif ($lastActivity->lt($today->copy()->subDay())) {
                $streak->update([
                    'current_streak'     => 1,
                    'last_activity_date' => $today,
                ]);
            }
        }
    }
}