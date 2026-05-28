<?php

namespace App;

enum PipelineStatus: string
{
    case PENDING = 'pending';
    case STARTED = 'started';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
}
