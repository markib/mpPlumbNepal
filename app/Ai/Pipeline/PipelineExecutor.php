<?php

namespace App\Ai\Pipeline;

use App\Ai\Context\PipelineContext;
use App\Events\StepCompletedEvent;

class PipelineExecutor
{
    public function execute(
        array $steps,
        PipelineContext $context
    ): PipelineContext {

        foreach ($steps as $stepClass) {

            $step = app($stepClass);

            $context = $step->handle($context);
            
            event(new StepCompletedEvent(
                pipelineId: $context->get('pipeline_id'),
                stepName: class_basename($stepClass),
               
            ));
        }

        return $context;
    }
}
