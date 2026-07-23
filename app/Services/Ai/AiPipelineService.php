<?php

namespace App\Services\AI;

use App\Jobs\Ai\RunPipelineJob;
use App\Models\AiPipeline;
use App\Models\User;
use App\PipelineStatus;

class AiPipelineService
{
    /**
     * Start a new AI pipeline.
     */
    public function start(
        User $user,
        array $input,
        ?array $image = null
    ): AiPipeline {
        $pipeline = AiPipeline::create([
            'user_id' => $user->id,
            'status' => PipelineStatus::PROCESSING,
            'input' => [
                ...$input,
                'image' => $image,
            ],
        ]);

        RunPipelineJob::dispatch(
            pipelineId: $pipeline->id,
            input: [
                'message' => $input['message'] ?? '',
                'image' => $image,
                'user_id' => $user->id,
            ]
        );

        return $pipeline;
    }
}
