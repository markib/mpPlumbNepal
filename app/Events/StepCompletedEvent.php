<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class StepCompletedEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public int $pipelineId, public string $stepName)
    {
        Log::info('StepCompletedEvent fired', [
            'pipelineId' => $pipelineId,
            'stepName' => $stepName,
        ]);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        logger()->info('broadcastOn executed', [
            'pipelineId' => $this->pipelineId,
        ]);

        return [
            new PrivateChannel("pipeline.{$this->pipelineId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'pipeline.step.completed';
    }

    /**
     * 🔥 OPTIMIZED PAYLOAD
     */
    public function broadcastWith(): array
    {
        // return match ($this->stepName) {

        //     'GenerateDiagnosisStep' => [
        //         'pipelineId' => $this->pipelineId,
        //         'stepName' => $this->stepName,

        //         'data' => [
        //             'diagnosis' =>
        //             $this->data['diagnosis'] ?? null,
        //         ],
        //     ],

        //     'GenerateRecommendationStep' => [
        //         'pipelineId' => $this->pipelineId,
        //         'stepName' => $this->stepName,

        //         'data' => [
        //             'recommendation' =>
        //             $this->data['recommendation'] ?? null,
        //         ],
        //     ],

        //     'StoreResultStep' => [
        //         'pipelineId' => $this->pipelineId,
        //         'stepName' => $this->stepName,

        //         'data' => [
        //             'result' =>
        //             $this->data['result'] ?? null,
        //         ],
        //     ],

        //     default => [
        //         'pipelineId' => $this->pipelineId,
        //         'stepName' => $this->stepName,
        //     ],
        // };
        return [
            'pipelineId' => $this->pipelineId,
            'stepName' => $this->stepName,
        ];
    }
}
