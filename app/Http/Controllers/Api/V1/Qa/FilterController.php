<?php

namespace App\Http\Controllers\Api\V1\Qa;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeDomain;
use App\Models\PlantType;
use App\Models\QuestionDomainLink;
use Illuminate\Http\JsonResponse;

class FilterController extends Controller
{
    public function plantTypes(): JsonResponse
    {
        return response()->json([
            'data' => PlantType::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function knowledgeDomains(): JsonResponse
    {
        return response()->json([
            'data' => KnowledgeDomain::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function domainLinks(): JsonResponse
    {
        return response()->json([
            'data' => QuestionDomainLink::query()
                ->with(['question', 'knowledgeDomain'])
                ->paginate((int) request('per_page', 20)),
        ]);
    }
}
