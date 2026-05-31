<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlumberProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'service_type_ids',
        'location',
        'latitude',
        'longitude',
        'is_available',
        'is_online',
        'available_since',
        'availability_notes',
        'verified',
        'rating',
        'last_location_update',
        'location_accuracy',
        'current_speed',
        'current_heading',
        'socket_id',
    ];

    protected $casts = [
        'service_type_ids' => 'array',
        'is_available' => 'boolean',
        'is_online' => 'boolean',
        'verified' => 'boolean',
        'rating' => 'float',
        'last_location_update' => 'datetime',
        'location_accuracy' => 'float',
        'current_speed' => 'float',
        'current_heading' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'plumber_profile_id');
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'plumber_skills')
            ->withTimestamps();
    }

    public function scopeOnline(Builder $query): Builder
    {
        return $query->where('is_online', true);
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('is_available', true);
    }

    public function scopeWithinRadius(Builder $query, float $latitude, float $longitude, float $radiusKm): Builder
    {
        if (config('database.default') === 'pgsql') {
            $point = sprintf('SRID=4326;POINT(%s %s)', $longitude, $latitude);

            return $query
                ->whereRaw('ST_DWithin(location, ST_GeogFromText(?), ?)', [$point, $radiusKm * 1000])
                ->selectRaw('plumber_profiles.*, ST_Distance(location, ST_GeogFromText(?)) AS distance_meters', [$point]);
        }

        return $query;
    }

    public function updateSocketId(string $socketId): void
    {
        $this->socket_id = $socketId;
        $this->save();
    }

    public function clearSocketId(): void
    {
        $this->socket_id = null;
        $this->save();
    }

    public function isOnline(): bool
    {
        return $this->is_online && $this->is_available;
    }
}
