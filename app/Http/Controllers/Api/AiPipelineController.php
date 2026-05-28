<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiPipeline;
use App\PipelineStatus;
use App\Services\AI\AiPipelineService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AiPipelineController extends Controller
{
    public function __construct(
        protected AiPipelineService $service
    ) {}

    public function start(Request $request): JsonResponse
    {
        $request->validate([
            'message' => ['required', 'string', 'min:3'],
        ]);

        $pipeline = $this->service->start(
            user: $request->user(),
            input: [
                'message' => $request->message,
            ],
            image: null
        );

        return response()->json([
            'status' => PipelineStatus::PENDING,
            'message' => 'Pipeline started',
            'pipeline_id' => $pipeline->id,
        ], 202);
    }

    public function show(int $pipelineId): JsonResponse
    {
        $pipeline = AiPipeline::findOrFail($pipelineId);

        return response()->json([
            'status' => $pipeline->status, // pending | processing | completed | failed
            'pipeline_id' => $pipeline->id,
            'result' => $pipeline->result,
            'error' => $pipeline->error_message,
        ]);
    }
}