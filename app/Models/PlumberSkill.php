<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlumberSkill extends Model
{
    protected $table = 'plumber_skills';

    protected $fillable = [
        'plumber_profile_id',
        'skill_id',
    ];

    public function plumberProfile(): BelongsTo
    {
        return $this->belongsTo(PlumberProfile::class);
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }
}