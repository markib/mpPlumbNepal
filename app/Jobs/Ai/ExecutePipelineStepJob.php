<?php

namespace App\Jobs\Ai;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExecutePipelineStepJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $pipelineId,
        public int $stepIndex,
        public array $steps,
        public array $context
    ) {}

    public function handle(): void
    {
        if (!isset($this->steps[$this->stepIndex])) {

            StorePipelineResultJob::dispatch(
                $this->pipelineId,
                $this->context
            );

            return;
        }

        $stepClass = $this->steps[$this->stepIndex];

        $step = app($stepClass);

        $start = microtime(true);

        try {
            $context = $step->handle($this->context);

            $duration = microtime(true) - $start;

            Log::info("Step executed", [
                'step' => $stepClass,
                'pipeline_id' => $this->pipelineId,
                'duration' => $duration
            ]);

            // next step
            ExecutePipelineStepJob::dispatch(
                pipelineId: $this->pipelineId,
                stepIndex: $this->stepIndex + 1,
                steps: $this->steps,
                context: $context
            );
        } catch (\Throwable $e) {

            Log::error("Pipeline step failed", [
                'step' => $stepClass,
                'error' => $e->getMessage()
            ]);

            RetryPipelineStepJob::dispatch(
                $this->pipelineId,
                $this->stepIndex,
                $this->steps,
                $this->context,
                $e->getMessage()
            );
        }
    }
}
