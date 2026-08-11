<?php

use App\Http\Controllers\QuizController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/quiz/generate', [QuizController::class, 'generate']);
    Route::post('/quiz/{id}/finish', [QuizController::class, 'finish']);
    Route::get('/quiz/{quiz_id}', [QuizController::class, 'show']);
});



require __DIR__ . '/auth.php';
