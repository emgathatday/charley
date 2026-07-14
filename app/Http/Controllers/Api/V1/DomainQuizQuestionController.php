<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\QuizQuestionRequest;
use App\Http\Resources\QuizQuestionResource;
use App\Models\KnowledgeDomain;
use App\Models\QuizQuestion;
use App\Services\KnowledgeDomainQuizService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class DomainQuizQuestionController extends Controller
{
    public function __construct(private readonly KnowledgeDomainQuizService $quizzes)
    {
    }

    public function index(KnowledgeDomain $knowledgeDomain): AnonymousResourceCollection
    {
        return QuizQuestionResource::collection(
            $knowledgeDomain->quizQuestions()->with('choices')->orderBy('sort_order')->paginate(50)
        );
    }

    public function store(QuizQuestionRequest $request, KnowledgeDomain $knowledgeDomain): QuizQuestionResource
    {
        return QuizQuestionResource::make($this->quizzes->createQuestion($knowledgeDomain, $request->validated(), $request->user()));
    }

    public function show(KnowledgeDomain $knowledgeDomain, QuizQuestion $quizQuestion): QuizQuestionResource
    {
        abort_unless($quizQuestion->knowledge_domain_id === $knowledgeDomain->id, 404);

        return QuizQuestionResource::make($quizQuestion->load('choices'));
    }

    public function update(QuizQuestionRequest $request, KnowledgeDomain $knowledgeDomain, QuizQuestion $quizQuestion): QuizQuestionResource
    {
        return QuizQuestionResource::make($this->quizzes->updateQuestion($knowledgeDomain, $quizQuestion, $request->validated(), $request->user()));
    }

    public function destroy(KnowledgeDomain $knowledgeDomain, QuizQuestion $quizQuestion): Response
    {
        $this->quizzes->deleteQuestion($knowledgeDomain, $quizQuestion);

        return response()->noContent();
    }
}
