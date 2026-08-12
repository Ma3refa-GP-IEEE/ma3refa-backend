<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Categories\CategoryController;
use App\Http\Controllers\Categories\SubcategoryController;
use App\Http\Controllers\QuizController;

Route::middleware('auth:sanctum')->group(function () { 
    // 1. Profile Route
    Route::get('/user/profile', [ProfileController::class, 'show']);

     // Quiz Routes
     Route::post('/quiz/generate', [QuizController::class, 'generate']);
     Route::post('/quiz/{id}/finish', [QuizController::class, 'finish']);
     Route::get('/quiz/{quiz_id}', [QuizController::class, 'show']);

    // 2. Categories & Subcategories Routes
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{category}/subcategories', [CategoryController::class, 'subcategories']);
    
    Route::get('/user/subcategories/{subcategory}/quizzes', [SubcategoryController::class, 'quizzes']);
});
    
require __DIR__.'/auth.php';   