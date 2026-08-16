<?php

namespace App\Listeners;

use App\Events\QuizFinished;
use App\Jobs\ComputeRecommendationsJob;
use App\Models\Quiz;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;

class CheckRecommendationBatch implements ShouldQueue
{
    public function handle(QuizFinished $event): void
    {   
        $batchSize = 5;
        $userId = $event->quiz->user_id;

        DB::transaction(function () use ($userId, $batchSize) {
            $batchQuizIds = Quiz::where('user_id', $userId)
                ->where('included_in_recommendation_batch', false)
                ->whereNotNull('finished_at')
                ->orderBy('created_at')
                ->limit($batchSize)
                ->lockForUpdate()
                ->pluck('id');

            if ($batchQuizIds->count() < $batchSize) {
                return;
            }

            Quiz::whereIn('id', $batchQuizIds)->update([
                'included_in_recommendation_batch' => true,
            ]);

            ComputeRecommendationsJob::dispatch($userId, $batchQuizIds->all());
        });
    }
}