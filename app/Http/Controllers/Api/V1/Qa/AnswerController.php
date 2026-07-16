<?php

namespace App\Http\Controllers\Api\V1\Qa;

use App\Http\Controllers\Controller;
use App\Http\Requests\Qa\StoreAnswerRequest;
use App\Http\Resources\Qa\AnswerResource;
use App\Models\Question;
use App\Services\Qa\AnswerModerationService;

class AnswerController extends Controller
{
    public function __construct(private readonly AnswerModerationService $answerModerationService) {}

    public function store(StoreAnswerRequest $request, Question $question): AnswerResource
    {
        abort_unless($question->status === 'published', 404);

        $data = $request->validated();
        $data['user_id'] = $request->user()->id;

        return new AnswerResource($this->answerModerationService->createAnswer($question, $data));
    }
}
