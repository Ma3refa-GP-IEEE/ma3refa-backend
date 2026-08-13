<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateQuizRequest;
use App\Http\Requests\FinishQuizRequest;
use App\Http\Resources\QuizDetailResource;
use App\Http\Resources\QuizResource;
use App\Models\Quiz;
use App\Models\Subcategory;
use App\Services\QuizService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
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
                $request->difficulty,
                $request->number_of_questions,
                Auth::id()
            );

            return new QuizResource($quiz);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    public function show($id)
    {
        $quiz = Quiz::with(['subcategory', 'questions', 'userAnswers'])->findOrFail($id);

        Gate::authorize('view', $quiz);

        return new QuizDetailResource($quiz);
    }

    public function finish(FinishQuizRequest $request, $id)
    {
        $quiz = Quiz::findOrFail($id);
        Gate::authorize('finish', $quiz);

        if ($quiz->finished_at !== null) {
            return response()->json([
                'message' => 'This quiz has already been finished.'
            ], 400);
        }

        $this->quizService->finishQuiz($quiz, $request->validated(), Auth::id());

        event(new QuizFinished($quiz));
        return response()->json([
            'message' => 'Quiz finished successfully'
        ], 200);
    }
}