<?php

namespace App\Models;

use App\PipelineStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiPipeline extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'status',
        'input',
        'result',
        'current_step',
        'error',
    ];

    protected $casts = [
        'input' => 'array',
        'status' => PipelineStatus::class,
        'result' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
