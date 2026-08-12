<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Categories\CategoryController;
use App\Http\Controllers\Categories\SubcategoryController;

Route::middleware('auth:sanctum')->group(function () { 
    // 1. Profile Route
    Route::get('/user/profile', [ProfileController::class, 'show']);

    // 2. Categories & Subcategories Routes
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{category}/subcategories', [CategoryController::class, 'subcategories']);
    Route::get('/user/subcategories/{subcategory}/quizzes', [SubcategoryController::class, 'quizzes']);
});