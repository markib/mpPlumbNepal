<?php

namespace App\Jobs\Ai;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RetryPipelineStepJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $pipelineId,
        public int $stepIndex,
        public array $steps,
        public array $context,
        public string $error
    ) {}

    public function handle(): void
    {
        // simple retry delay
        sleep(2);

        ExecutePipelineStepJob::dispatch(
            $this->pipelineId,
            $this->stepIndex,
            $this->steps,
            $this->context
        );
    }
}
