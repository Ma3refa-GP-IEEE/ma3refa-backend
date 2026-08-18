<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateQuizRequest;
use App\Http\Requests\FinishQuizRequest;
use App\Http\Resources\QuizDetailResource;
use App\Http\Resources\QuizResource;
use App\Models\Quiz;
use App\Models\Subcategory;
use App\Services\QuizService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use App\Events\QuizFinished;

class QuizController extends Controller
{
    protected QuizService $quizService;

    public function __construct(QuizService $quizService)
    {
        $this->quizService = $quizService;
    }

    public function generate(GenerateQuizRequest $request)
    {
        $subcategory = Subcategory::with(['category', 'allowedTopics'])->findOrFail($request->subcategory_id);

        try {
            $quiz = $this->quizService->generateQuiz(
                $subcategory,
                (string) $request->difficulty,
                (int) $request->number_of_questions,
                Auth::id(),
                (array) $request->input('topics', [])
            );

            return new QuizResource($quiz);
        } catch (\Exception $e) {
            Log::error('Quiz Generation Failed: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id'   => Auth::id(),
            ]);

            $statusCode = is_numeric($e->getCode()) && $e->getCode() >= 400 && $e->getCode() < 600 
                ? (int) $e->getCode() 
                : 500;

            $message = $statusCode === 500 
                ? 'An unexpected error occurred while generating the quiz.' 
                : $e->getMessage();

            return response()->json(['message' => $message], $statusCode);
        }
    }

    public function show(int $id): QuizDetailResource
    {
        $quiz = Quiz::with(['subcategory', 'questions', 'userAnswers'])->findOrFail($id);

        Gate::authorize('view', $quiz);

        return new QuizDetailResource($quiz);
    }

    public function finish(FinishQuizRequest $request, int $id): JsonResponse
    {
        $quiz = Quiz::findOrFail($id);
        Gate::authorize('finish', $quiz);

        if ($quiz->finished_at !== null) {
            return response()->json([
                'message' => 'This quiz has already been finished.'
            ], 400);
        }

        try {
            $this->quizService->finishQuiz($quiz, $request->validated(), Auth::id());

            $quiz->refresh();

            event(new QuizFinished($quiz));

            return response()->json([
                'message' => 'Quiz finished successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Quiz Finish Failed: ' . $e->getMessage(), [
                'exception' => $e,
                'quiz_id'   => $id,
                'user_id'   => Auth::id(),
            ]);

            $statusCode = is_numeric($e->getCode()) && $e->getCode() >= 400 && $e->getCode() < 600 
                ? (int) $e->getCode() 
                : 500;

            return response()->json([
                'message' => $statusCode === 500 ? 'An unexpected error occurred.' : $e->getMessage()
            ], $statusCode);
        }
    }
}