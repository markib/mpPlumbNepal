<?php

namespace App\Jobs\Ai;

use App\Models\AiPipeline;
use App\PipelineStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class StorePipelineResultJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function __construct(
        public int $pipelineId,
        public array $result
    ) {}

    public function handle(): void
    {
        AiPipeline::where('id', $this->pipelineId)
            ->update([
                'status' => PipelineStatus::COMPLETED,
                'result' => json_encode($this->result),
            ]);
    }
}
