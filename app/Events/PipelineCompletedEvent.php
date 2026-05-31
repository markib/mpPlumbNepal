<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class PipelineCompletedEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public int $pipelineId,
        public string $status
    ) {
        //
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("pipeline.{$this->pipelineId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'pipeline.completed';
    }

    /**
     * 💡 Small payload footprint sent to Reverb
     */
    public function broadcastWith(): array
    {
        return [
            'pipelineId' => $this->pipelineId,
            'status' => $this->status, // e.g., 'completed', 'failed', 'rate_limited'
        ];
    }
}
