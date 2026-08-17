<?php

namespace App\Http\Controllers\Api\V1\Admin\Qa;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Qa\ReputationAdjustmentRequest;
use App\Http\Resources\Admin\Qa\ReputationAdjustmentResource;
use App\Services\Qa\ReputationLedgerService;

class ReputationAdjustmentController extends Controller
{
    public function __construct(private readonly ReputationLedgerService $reputationLedgerService) {}

    public function store(ReputationAdjustmentRequest $request): ReputationAdjustmentResource
    {
        $data = $request->validated();

        return new ReputationAdjustmentResource($this->reputationLedgerService->recordManualAdjustment(
            $data['user_id'],
            $data['points'],
            $data['reason'],
            $request->user()->id,
        ));
    }
}
