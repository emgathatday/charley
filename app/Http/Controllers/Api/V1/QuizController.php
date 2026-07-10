<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\KnowledgeDomain\QuizQuestionRequest;
use App\Http\Requests\KnowledgeDomain\QuizStoreRequest;
use App\Http\Requests\KnowledgeDomain\QuizUpdateRequest;
use App\Http\Resources\QuizResource;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    public function index(Request $request)
    {
        $query = Quiz::query()->with('knowledgeDomain')->withCount('attempts');

        if ($request->user()?->role !== 'admin' || ! $request->boolean('include_unpublished')) {
            $query->published();
        }

        if ($request->filled('knowledge_domain_id')) {
            $query->where('knowledge_domain_id', $request->integer('knowledge_domain_id'));
        }

        if ($request->filled('q')) {
            $search = $request->string('q');
            $query->where(function ($nested) use ($search): void {
                $nested->where('title', 'ilike', "%{$search}%")
                    ->orWhere('slug', 'ilike', "%{$search}%");
            });
        }

        return QuizResource::collection($query->latest()->paginate($request->integer('per_page', 15)));
    }

    public function store(QuizStoreRequest $request)
    {
        $data = $request->validated();
        $data['status'] ??= Quiz::STATUS_DRAFT;
        $data['created_by'] = $request->user()?->id;

        return new QuizResource(Quiz::query()->create($data)->load('knowledgeDomain', 'questions'));
    }

    public function show(Request $request, Quiz $quiz)
    {
        abort_unless($quiz->isPublished() || $request->user()?->role === 'admin', 404);

        return new QuizResource($quiz->load(['knowledgeDomain', 'questions'])->loadCount('attempts'));
    }

    public function update(QuizUpdateRequest $request, Quiz $quiz)
    {
        $quiz->update($request->validated());

        return new QuizResource($quiz->refresh()->load('knowledgeDomain', 'questions'));
    }

    public function destroy(Quiz $quiz)
    {
        $quiz->update(['status' => Quiz::STATUS_ARCHIVED]);

        return new QuizResource($quiz->refresh()->load('knowledgeDomain'));
    }

    public function storeQuestion(QuizQuestionRequest $request, Quiz $quiz)
    {
        $quiz->questions()->create($request->validated() + ['question_type' => QuizQuestion::TYPE_SINGLE_CHOICE]);

        return new QuizResource($quiz->refresh()->load('knowledgeDomain', 'questions'));
    }

    public function updateQuestion(QuizQuestionRequest $request, Quiz $quiz, QuizQuestion $quizQuestion)
    {
        abort_unless($quizQuestion->quiz_id === $quiz->id, 404);
        $quizQuestion->update($request->validated());

        return new QuizResource($quiz->refresh()->load('knowledgeDomain', 'questions'));
    }

    public function destroyQuestion(Quiz $quiz, QuizQuestion $quizQuestion)
    {
        abort_unless($quizQuestion->quiz_id === $quiz->id, 404);
        $quizQuestion->delete();

        return new QuizResource($quiz->refresh()->load('knowledgeDomain', 'questions'));
    }
}