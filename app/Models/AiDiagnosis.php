<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiDiagnosis extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'issue_type',
        'urgency',
        'price_min',
        'price_max',
        'service',
        'confidence',
        'summary',
        'raw',
        'model',
        'prompt_version',
    ];

    protected $casts = [
        'raw' => 'array',
        'confidence' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
