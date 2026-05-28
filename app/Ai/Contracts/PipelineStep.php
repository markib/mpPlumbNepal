<?php

namespace App\Ai\Contracts;

use App\Ai\Context\PipelineContext;

interface PipelineStep
{
    public function handle(
        PipelineContext $context
    ): PipelineContext;
}
