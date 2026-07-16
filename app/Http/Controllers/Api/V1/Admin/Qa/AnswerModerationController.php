<?php

namespace App\Http\Controllers\Api\V1\Admin\Qa;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Qa\FeatureAnswerRequest;
use App\Http\Requests\Admin\Qa\ReorderAnswersRequest;
use App\Http\Resources\Admin\Qa\AnswerModerationResource;
use App\Models\Answer;
use App\Models\Question;
use App\Services\Qa\AnswerModerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AnswerModerationController extends Controller
{
    public function __construct(private readonly AnswerModerationService $answerModerationService) {}

    public function index(): AnonymousResourceCollection
    {
        $answers = Answer::query()
            ->with(['question', 'user'])
            ->when(request('question_id'), fn ($query, $questionId) => $query->where('question_id', $questionId))
            ->when(request()->has('is_admin_featured'), fn ($query) => $query->where('is_admin_featured', request()->boolean('is_admin_featured')))
            ->latest()
            ->paginate((int) request('per_page', 20));

        return AnswerModerationResource::collection($answers);
    }

    public function feature(FeatureAnswerRequest $request, Answer $answer): AnswerModerationResource
    {
        return new AnswerModerationResource($this->answerModerationService->feature(
            $answer,
            $request->validated('confidence_level'),
            $request->validated('admin_rank_order'),
        ));
    }

    public function unfeature(Answer $answer): AnswerModerationResource
    {
        return new AnswerModerationResource($this->answerModerationService->unfeature($answer));
    }

    public function reorder(ReorderAnswersRequest $request, Question $question): JsonResponse
    {
        $this->answerModerationService->reorderFeaturedAnswers($question, $request->answerRankMap());

        return response()->json(['status' => 'ok']);
    }
}
