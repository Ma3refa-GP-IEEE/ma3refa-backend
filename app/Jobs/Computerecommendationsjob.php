<?php

namespace App\Jobs;

use App\Models\AllowedTopic;
use App\Models\Quiz;
use App\Models\Recommendation;
use App\Models\Subcategory;
use App\Models\UserAnswer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ComputeRecommendationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(
        public int $userId,
        public array $quizIds,
    ) {}

    public function handle(): void
    {
        $quizzes = Quiz::with([
            'subcategory',
            'userAnswers.question.allowedTopic'
        ])->whereIn('id', $this->quizIds)->get();

        if ($quizzes->count() !== count($this->quizIds)) {
            Log::warning(
                'ComputeRecommendationsJob: quiz set changed before processing, aborting.',
                [
                    'user_id' => $this->userId,
                    'quiz_ids' => $this->quizIds,
                ]
            );

            return;
        }

        $payload = [
            'student_id' => $this->userId,
            'recent_quizzes' => $quizzes->map(
                fn(Quiz $quiz) => [
                    'quiz_id' => $quiz->id,
                    'subcategory' => $quiz->subcategory->name,
                    'difficulty' => $quiz->difficulty,
                    'answers' => $quiz->userAnswers->map(
                        fn(UserAnswer $answer) => [
                            'topic' => $answer->question->allowedTopic->topic_name,
                            'is_correct' => (bool) $answer->is_correct,
                        ]
                    )->values(),
                ]
            )->values(),
        ];

        $response = Http::timeout(15)
            ->post(
                config('services.recommendations.url'),
                $payload
            );

        if (! $response->successful()) {
            Log::warning(
                'ComputeRecommendationsJob: recommendation service call failed.',
                [
                    'user_id' => $this->userId,
                    'status' => $response->status(),
                ]
            );

            return;
        }

        $rawRecommendations = $response->json('recommendations', []);
        $mapped = [];

        foreach ($rawRecommendations as $rec) {
            $subcategory = Subcategory::where(
                'name',
                $rec['subcategory']
            )->first();

            $allowedTopic = AllowedTopic::where(
                'topic_name',
                $rec['topic']
            )->first();

            if (! $subcategory || ! $allowedTopic) {
                Log::warning(
                    'ComputeRecommendationsJob: could not map subcategory/topic, skipping row.',
                    $rec
                );

                continue;
            }

            $mapped[] = [
                'user_id' => $this->userId,
                'subcategory_id' => $subcategory->id,
                'allowed_topic_id' => $allowedTopic->id,
                'difficulty' => $rec['difficulty'],
                'created_at' => now(),
            ];
        }

        DB::transaction(function () use ($mapped) {
            Quiz::whereIn('id', $this->quizIds)->update([
                'included_in_recommendation_batch' => true,
            ]);

            Recommendation::where('user_id', $this->userId)->delete();

            if (! empty($mapped)) {
                Recommendation::insert($mapped);
            }
        });
    }
}
