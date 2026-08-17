<?php

namespace App\Http\Controllers\Api\V1\Admin\Qa;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\Qa\QuestionModerationResource;
use App\Models\Question;
use App\Services\Qa\QuestionWorkflowService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class QuestionModerationController extends Controller
{
    public function __construct(private readonly QuestionWorkflowService $questionWorkflowService) {}

    public function index(): AnonymousResourceCollection
    {
        $questions = Question::query()
            ->with(['user', 'weeklyTheme', 'plantType', 'knowledgeDomains'])
            ->when(request('status'), fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->paginate((int) request('per_page', 20));

        return QuestionModerationResource::collection($questions);
    }

    public function show(Question $question): QuestionModerationResource
    {
        return new QuestionModerationResource($question->load(['user', 'weeklyTheme', 'plantType', 'knowledgeDomains', 'answers.user']));
    }

    public function publish(Question $question): QuestionModerationResource
    {
        return new QuestionModerationResource($this->questionWorkflowService->publish($question));
    }

    public function hide(Question $question): QuestionModerationResource
    {
        return new QuestionModerationResource($this->questionWorkflowService->hide($question));
    }

    public function flag(Question $question): QuestionModerationResource
    {
        return new QuestionModerationResource($this->questionWorkflowService->flag($question));
    }
}
