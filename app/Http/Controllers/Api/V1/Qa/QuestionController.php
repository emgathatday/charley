<?php

namespace App\Http\Controllers\Api\V1\Qa;

use App\Http\Controllers\Controller;
use App\Http\Requests\Qa\StoreQuestionRequest;
use App\Http\Resources\Qa\QuestionResource;
use App\Models\Question;
use App\Services\Qa\QuestionWorkflowService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class QuestionController extends Controller
{
    public function __construct(private readonly QuestionWorkflowService $questionWorkflowService) {}

    public function index(): AnonymousResourceCollection
    {
        $questions = Question::query()
            ->with(['weeklyTheme', 'plantType', 'knowledgeDomains'])
            ->where('status', 'published')
            ->when(request('plant_type_id'), fn ($query, $plantTypeId) => $query->where('plant_type_id', $plantTypeId))
            ->when(request('weekly_theme_id'), fn ($query, $weeklyThemeId) => $query->where('weekly_theme_id', $weeklyThemeId))
            ->when(request('knowledge_domain_id'), fn ($query, $domainId) => $query->whereHas('knowledgeDomains', fn ($domains) => $domains->whereKey($domainId)))
            ->latest()
            ->paginate((int) request('per_page', 20));

        return QuestionResource::collection($questions);
    }

    public function store(StoreQuestionRequest $request): QuestionResource
    {
        $data = $request->validated();

        if (! empty($data['on_behalf_of_partner_id']) || ! empty($data['posted_by_admin_id'])) {
            Gate::authorize('postOnBehalf', Question::class);
        }

        $data['user_id'] = $request->user()->id;

        $question = $this->questionWorkflowService->createQuestion($data);

        if (! empty($data['knowledge_domain_ids'])) {
            $question = $this->questionWorkflowService->linkKnowledgeDomains($question, $data['knowledge_domain_ids']);
        }

        return new QuestionResource($question->load(['weeklyTheme', 'plantType', 'knowledgeDomains']));
    }

    public function show(Question $question): QuestionResource
    {
        abort_unless($question->status === 'published', 404);

        return new QuestionResource($question->load([
            'weeklyTheme',
            'plantType',
            'knowledgeDomains',
            'answers' => fn ($query) => $query->orderByDesc('is_admin_featured')->orderBy('admin_rank_order')->oldest(),
        ]));
    }
}
