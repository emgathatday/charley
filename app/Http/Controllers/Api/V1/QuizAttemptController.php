<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\KnowledgeDomain\QuizAttemptSubmitRequest;
use App\Http\Resources\QuizAttemptResource;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Services\Library\QuizScoringService;
use Illuminate\Http\Request;

class QuizAttemptController extends Controller
{
    public function __construct(private readonly QuizScoringService $quizScoringService)
    {
    }

    public function index(Request $request)
    {
        $query = QuizAttempt::query()->with(['quiz.knowledgeDomain', 'bestScore']);

        if ($request->user()?->role !== 'admin') {
            $query->where('user_id', $request->user()->id);
        }

        if ($request->filled('quiz_id')) {
            $query->where('quiz_id', $request->integer('quiz_id'));
        }

        return QuizAttemptResource::collection($query->latest()->paginate($request->integer('per_page', 15)));
    }

    public function store(QuizAttemptSubmitRequest $request, Quiz $quiz)
    {
        $attempt = $this->quizScoringService->submitQuiz($quiz, $request->user(), $request->validated()['answers']);

        return new QuizAttemptResource($attempt);
    }

    public function show(Request $request, QuizAttempt $quizAttempt)
    {
        abort_unless($request->user()?->role === 'admin' || $quizAttempt->user_id === $request->user()?->id, 403);

        return new QuizAttemptResource($quizAttempt->load(['quiz.knowledgeDomain', 'bestScore']));
    }
}