<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Skill extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'icon',
    ];

    public function plumberProfiles(): BelongsToMany
    {
        return $this->belongsToMany(PlumberProfile::class, 'plumber_skills')
            ->withTimestamps();
    }
}
