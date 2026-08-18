<?php

namespace App\Services;

use App\Models\Question;
use App\Models\Quiz;
use App\Models\Subcategory;
use App\Models\UserAnswer;
use App\Models\UserSubcategoryPoint;
use App\Models\Streak;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class QuizService
{
   public function generateQuiz(
        Subcategory $subcategory, 
        string $difficulty, 
        int $numberOfQuestions, 
        int $userId, 
        array $selectedTopics = []
    ): Quiz {
        $difficulty = strtolower($difficulty);

        $cachedQuestions = collect();
        if (empty($selectedTopics)) {
            $cachedQuestions = $this->getCachedQuestions($subcategory, $difficulty, $numberOfQuestions, $userId);
        }

        if ($cachedQuestions->count() >= $numberOfQuestions) {
            $quiz = $this->storeQuizFromCache($cachedQuestions, $subcategory->id, $difficulty, $userId);
        } else {
            $topicsToSend = null;

            if (!empty($selectedTopics)) {
                $cleanedTopics = array_map(fn($t) => trim(strtolower((string) $t)), $selectedTopics);

                $matchedTopics = $subcategory->allowedTopics()
                    ->where(function ($query) use ($cleanedTopics) {
                        foreach ($cleanedTopics as $topic) {
                            $query->orWhereRaw('LOWER(topic_name) = ?', [$topic]);
                        }
                    })
                    ->pluck('topic_name');

                if ($matchedTopics->isNotEmpty()) {
                    $topicsToSend = $matchedTopics;
                }
            }

            if (empty($topicsToSend)) {
                $topicsToSend = $subcategory->allowedTopics->pluck('topic_name');
            }

            $excludedConcepts = Question::whereHas('allowedTopic', function ($query) use ($subcategory) {
                    $query->where('subcategory_id', $subcategory->id);
                })
                ->whereNotNull('concept_tag')
                ->distinct()
                ->inRandomOrder()
                ->limit(50)
                ->pluck('concept_tag')
                ->values()
                ->toArray();

            $payload = [
                "category"          => $subcategory->category->name,
                "sub_category"      => $subcategory->name,
                "difficulty"        => ucfirst($difficulty),
                "language"          => "Arabic",
                "allowed_topics"    => $topicsToSend->values()->toArray(),
                "num_questions"     => $numberOfQuestions,
                "excluded_concepts" => $excludedConcepts,
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
        DB::transaction(function () use ($quiz, $data, $userId) {
            // قفل سجل الكويز لضمان عدم إنهائه في نفس الوقت
            $lockedQuiz = Quiz::where('id', $quiz->id)->lockForUpdate()->first();

            if ($lockedQuiz->finished_at !== null) {
                throw new \Exception('This quiz has already been finished.', 400);
            }

            $now = now();

            $questionIds = collect($data['answers'])->pluck('question_id');
            
            // التأكد من جلب الأسئلة التابعة لهذا الكويز فقط
            $questions = $lockedQuiz->questions()->whereIn('questions.id', $questionIds)->get()->keyBy('id');

            $userAnswers = [];
            $correctCount = 0;

            foreach ($data['answers'] as $answer) {
                $qId = $answer['question_id'] ?? null;

                // تجاهل أي سؤال لا ينتمي لهذا الكويز
                if (!isset($questions[$qId])) {
                    continue;
                }

                $question = $questions[$qId];
                $actualIsCorrect = strtolower(trim((string)($answer['selected_answer'] ?? ''))) === strtolower(trim((string)$question->correct_answer));

                if ($actualIsCorrect) {
                    $correctCount++;
                }

                $userAnswers[] = [
                    'user_id'         => $userId,
                    'quiz_id'         => $lockedQuiz->id,
                    'question_id'     => $qId,
                    'selected_answer' => $answer['selected_answer'] ?? null,
                    'is_correct'      => $actualIsCorrect,
                    'answered_at'     => $now,
                ];
            }

            if (!empty($userAnswers)) {
                UserAnswer::insert($userAnswers);
            }

            $multiplier = match (strtolower($lockedQuiz->difficulty ?? 'easy')) {
                'medium' => 2,
                'hard'   => 3,
                default  => 1,
            };

            $calculatedScore = $correctCount * $multiplier;

            $lockedQuiz->update([
                'score'       => $calculatedScore,
                'finished_at' => $now,
            ]);

            $userPoint = UserSubcategoryPoint::where('user_id', $userId)
                ->where('subcategory_id', $lockedQuiz->subcategory_id)
                ->lockForUpdate()
                ->first();

            if (!$userPoint) {
                $userPoint = UserSubcategoryPoint::create([
                    'user_id'        => $userId,
                    'subcategory_id' => $lockedQuiz->subcategory_id,
                    'total_points'   => 0,
                ]);
            }

            $userPoint->increment('total_points', $calculatedScore);

            $this->updateUserStreak($userId);
        });
    }

    private function getCachedQuestions(Subcategory $subcategory, string $difficulty, int $limit, int $userId)
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

    private function storeQuizFromCache($questions, int $subcategoryId, string $difficulty, int $userId): Quiz
    {
        return DB::transaction(function () use ($questions, $subcategoryId, $difficulty, $userId) {
            $quiz = Quiz::create([
                'user_id'         => $userId,
                'subcategory_id'  => $subcategoryId,
                'difficulty'      => $difficulty,
                'score'           => 0,
                'total_questions' => $questions->count(),
                'created_at'      => now(),
            ]);

            $attachData = [];
            foreach ($questions as $index => $question) {
                $attachData[$question->id] = ['question_order' => $index + 1];
            }

            $quiz->questions()->attach($attachData);

            return $quiz;
        });
    }

    private function callAiService(array $payload): array
    {
        $aiUrl = config('services.ai_engine.url');

        $response = Http::withHeaders([
            'x-internal-key' => config('services.ai_engine.secret_key'),
            'Accept'         => 'application/json',
        ])
        ->timeout(15)
        ->retry(2, 200)
        ->post($aiUrl, $payload);

        if ($response->failed()) {
            Log::error('AI Engine Service Failed', [
                'status'  => $response->status(),
                'body'    => $response->body(),
                'payload' => $payload,
            ]);

            throw new \Exception('Failed to generate questions from AI service.', 502);
        }

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
                'concept_tag'      => $question['concept_tag'] ?? null ,
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

    private function storeQuizWithQuestions(array $mappedQuestions, Subcategory $subcategory, string $difficulty, int $numberOfQuestions, int $userId): Quiz
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

            $attachData = [];

            foreach ($mappedQuestions as $index => $question) {
                $questionRecord = Question::firstOrCreate(
                    ['description' => $question['description']],
                    [
                        'level'            => $difficulty,
                        'concept_tag'      => $question['concept_tag'], // 
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

                $attachData[$questionRecord->id] = ['question_order' => $index + 1];
            }

            $quiz->questions()->attach($attachData);

            return $quiz;
        });
    }
    private function updateUserStreak(int $userId): void
    {
        $today = Carbon::today();

        $streak = Streak::where('user_id', $userId)->lockForUpdate()->first();

        if (!$streak) {
            Streak::create([
                'user_id'            => $userId,
                'current_streak'     => 1,
                'last_activity_date' => $today,
            ]);
            return;
        }

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