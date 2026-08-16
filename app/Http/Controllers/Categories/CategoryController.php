<?php

namespace App\Http\Controllers\Categories;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Recommendation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = Category::select('id', 'name')->get();

        $recommendations = Recommendation::where('user_id', Auth::id())
            ->with([
                'subcategory:id,name',
                'allowedTopic:id,topic_name',
            ])
            ->get()
            ->map(fn (Recommendation $r) => [
                'subcategory_id' => $r->subcategory_id,
                'subcategory'    => $r->subcategory?->name,
                'topic'          => $r->allowedTopic?->topic_name,
                'difficulty'     => $r->difficulty,
            ])
            ->values();

        return response()->json([
            'categories'      => $categories,
            'recommendations' => $recommendations,
        ]);
    }

    public function subcategories(int $category_id): JsonResponse
    {
        $category = Category::find($category_id);

        if (! $category) {
            return response()->json([
                'message' => 'category_id does not exist',
            ], 404);
        }

        $subcategories = Subcategory::where('category_id', $category_id)
            ->select('id', 'name')
            ->get();

        return response()->json([
            'category_id'   => $category_id,
            'subcategories' => $subcategories,
        ]);
    }
}