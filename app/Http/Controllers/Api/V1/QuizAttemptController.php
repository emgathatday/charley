<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\QuizAttemptSubmitRequest;
use App\Http\Resources\QuizAttemptResource;
use App\Models\KnowledgeDomain;
use App\Models\QuizAttempt;
use App\Services\KnowledgeDomainQuizService;
use Illuminate\Http\Request;

class QuizAttemptController extends Controller
{
    public function __construct(private readonly KnowledgeDomainQuizService $quizzes)
    {
    }

    public function store(Request $request, KnowledgeDomain $knowledgeDomain): QuizAttemptResource
    {
        return QuizAttemptResource::make($this->quizzes->startAttempt(
            $knowledgeDomain,
            $request->user(),
            (int) $request->integer('question_limit', 50),
            (float) $request->input('pass_threshold', 80.00),
        ));
    }

    public function submit(QuizAttemptSubmitRequest $request, QuizAttempt $quizAttempt): QuizAttemptResource
    {
        abort_unless($quizAttempt->user_id === $request->user()->id || $request->user()->role === 'admin', 403);

        return QuizAttemptResource::make($this->quizzes->submitAttempt($quizAttempt, $request->validated('answers')));
    }
}
