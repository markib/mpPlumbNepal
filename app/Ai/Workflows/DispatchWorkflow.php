<?php

namespace App\Ai\Workflows;

use App\Ai\Steps\RunDispatchAgentStep;
use App\Ai\Steps\StoreResultStep;

class DispatchWorkflow
{
    /**
     * Define the steps for the dispatch workflow.
     */
    public function steps(): array
    {
        return [
            RunDispatchAgentStep::class,
            StoreResultStep::class,
        ];
    }
}
