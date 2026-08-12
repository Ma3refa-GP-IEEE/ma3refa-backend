<?php

use App\Http\Controllers\Categories\CategoryController;
use App\Http\Controllers\Categories\SubcategoryController;
use App\Http\Controllers\QuizController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

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