<?php

namespace App\Jobs\Ai;

use App\Ai\Context\PipelineContext;
use App\Ai\Pipeline\PipelineExecutor;
use App\Ai\Workflows\DiagnosisWorkflow;
use App\Events\PipelineCompletedEvent;
use App\Models\AiPipeline;
use App\PipelineStatus;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RunPipelineJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public int $pipelineId,
        public array $input
    ) {}

    public function handle(
        PipelineExecutor $executor
    ): void {
        $pipeline = AiPipeline::findOrFail($this->pipelineId);

        try {

            $workflow = app(DiagnosisWorkflow::class);

            $context = new PipelineContext(['pipeline_id' => $pipeline->id, ...$this->input]);

            $executor->execute(
                $workflow->steps(),
                $context
            );
            // Log::info('AI Pipeline executed successfully', [
            //     'result' => $context->get('result'),
            //     'pipeline_id' => $pipeline->id,
            // ]);
            $pipeline->update([
                'status' => PipelineStatus::COMPLETED,
                'result' => $context->get('result'),
            ]);

            event(new PipelineCompletedEvent(
                pipelineId: $pipeline->id,
                status: PipelineStatus::COMPLETED->value,
            ));
            
            // Log::info('AI Pipeline completed', [
            //     'pipeline_id' => $pipeline->id,
            // ]);
        } catch (\Throwable $e) {

            $pipeline->update([
                'status' => PipelineStatus::FAILED,
                'error_message' => $e->getMessage(),
            ]);

            Log::error('AI Pipeline failed', [
                'pipeline_id' => $pipeline->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
