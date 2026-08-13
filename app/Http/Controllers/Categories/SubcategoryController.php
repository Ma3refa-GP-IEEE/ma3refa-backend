<?php

namespace App\Http\Controllers\Categories;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\Subcategory;
use App\Models\UserSubcategoryPoint;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class SubcategoryController extends Controller
{
    public function quizzes(Request $request, int $subcategory_id): JsonResponse
    {
        $userId = Auth::id();

        $subcategory = Subcategory::find($subcategory_id);

        if (! $subcategory) {
            return response()->json([
                'message' => 'subcategory_id does not exist',
            ], 404);
        }

        $perPage = (int) $request->query('per_page', 10);
        $page = (int) $request->query('page', 1);

        $paginated = Quiz::where('user_id', $userId)
            ->where('subcategory_id', $subcategory_id)
            ->whereNotNull('finished_at')
            ->orderByDesc('created_at')
            ->paginate($perPage, ['*'], 'page', $page);

        if ($paginated->isEmpty()) {
            return response()->json([
                'message' => 'the user has no quizzes in this subcategory',
            ], 404);
        }

        $totalPoints = UserSubcategoryPoint::where('user_id', $userId)
            ->where('subcategory_id', $subcategory_id)
            ->value('total_points') ?? 0;

        return response()->json([
            'subcategory_id' => $subcategory_id,
            'subcategory'    => $subcategory->name,
            'total_points'   => (int) $totalPoints,
            'quizzes'        => $paginated->getCollection()->map(fn(Quiz $q) => [
                'quiz_id'         => $q->id,
                'difficulty'      => $q->difficulty,
                'score'           => $q->score,
                'total_questions' => $q->total_questions,
                'created_at'      => $q->created_at,
            ])->values(),
            'pagination' => [
                'current_page'  => $paginated->currentPage(),
                'per_page'      => $paginated->perPage(),
                'total_quizzes' => $paginated->total(),
                'total_pages'   => $paginated->lastPage(),
            ],
        ], 200);
    }
}