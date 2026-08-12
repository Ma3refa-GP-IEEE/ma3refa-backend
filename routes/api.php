<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Categories\CategoryController;
use App\Http\Controllers\Categories\SubcategoryController;
use App\Http\Controllers\QuizController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class ProfileController extends Controller
{
    /**
     * Get authenticated user profile data with subcategory points and quiz brief.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->load([
            'streak',
            'subcategoryPoints.subcategory:id,name'
        ]);

        $completedQuizzesCount = $user->quizzes()
            ->whereNotNull('finished_at')
            ->count();

        $totalPointsSum = (int) $user->subcategoryPoints()->sum('total_points');

        $subcategoryPoints = $user->subcategoryPoints->map(function ($point) {
            return [
                'subcategory_id'   => $point->subcategory_id,
                'subcategory_name' => $point->subcategory->name ?? null,
                'total_points'     => $point->total_points,
            ];
        });

        return response()->json([
            'user' => [
                'id'     => $user->id,
                'name'   => $user->name,
                'email'  => $user->email,
                'age'    => $user->age,
                'gender' => $user->gender,
            ],
            'streak' => [
                'current'       => $user->streak->current_streak ?? 0,
                'last_activity' => $user->streak->last_activity_date ?? null,
            ],
            'stats' => [
                'completed_quizzes' => $completedQuizzesCount,
                'total_points'      => $totalPointsSum,
            ],
            'subcategory_points' => $subcategoryPoints,
        ], 200);
    }
}
Route::middleware('auth:sanctum')->group(function () {
    // Quiz Routes
    Route::post('/quiz/generate', [QuizController::class, 'generate']);
    Route::post('/quiz/{id}/finish', [QuizController::class, 'finish']);
    Route::get('/quiz/{quiz_id}', [QuizController::class, 'show']);

    // Category & Subcategory Routes
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{category}/subcategories', [CategoryController::class, 'subcategories']);
    Route::get('/user/subcategories/{subcategory}/quizzes', [SubcategoryController::class, 'quizzes']);
});

require __DIR__ . '/auth.php';
