<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
