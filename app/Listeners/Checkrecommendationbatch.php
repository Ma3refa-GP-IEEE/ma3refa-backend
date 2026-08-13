<?php

namespace App\Listeners;

use App\Events\QuizFinished;
use App\Jobs\ComputeRecommendationsJob;
use App\Models\Quiz;
use Illuminate\Contracts\Queue\ShouldQueue;

class CheckRecommendationBatch implements ShouldQueue
{
    public function handle(QuizFinished $event): void
    {
        $userId = $event->quiz->user_id;

        $pendingCount = Quiz::where('user_id', $userId)
            ->where('included_in_recommendation_batch', false)
            ->whereNotNull('finished_at')
            ->count();

        if ($pendingCount < 5) {
            return;
        }

        $batchQuizIds = Quiz::where('user_id', $userId)
            ->where('included_in_recommendation_batch', false)
            ->whereNotNull('finished_at')
            ->orderBy('created_at')
            ->limit(5)
            ->pluck('id');

        ComputeRecommendationsJob::dispatch($userId, $batchQuizIds->all());
    }
}