<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'plumber_profile_id',
        'accepted_by_id',
        'service_type_id',
        'status_id',
        'workflow_status',
        'payment_method',
        'amount',
        'is_emergency',
        'landmark',
        'ward_number',
        'tole_name',
        'service_notes',
        'latitude',
        'longitude',
        'pickup_location',
        'pickup_latitude',
        'pickup_longitude',
        'contract_terms',
        'contract_start_code',
        'contracted_at',
        'ai_diagnosis_id',
        'broadcast_expires_at',
        'broadcast_status',
        'min_rating_required',
    ];

    protected $casts = [
        'amount' => 'integer',
        'is_emergency' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
        'pickup_latitude' => 'float',
        'pickup_longitude' => 'float',
        'contract_terms' => 'array',
        'job_order_json' => 'array',
        'contracted_at' => 'datetime',
        'job_started_at' => 'datetime',
        'broadcast_expires_at' => 'datetime',
        'min_rating_required' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plumber(): BelongsTo
    {
        return $this->belongsTo(PlumberProfile::class, 'plumber_profile_id');
    }

    public function acceptedBy(): BelongsTo
    {
        return $this->belongsTo(PlumberProfile::class, 'accepted_by_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(BookingStatus::class);
    }

    public function proposals()
    {
        return $this->hasMany(BookingProposal::class);
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    public function aiDiagnosis(): BelongsTo
    {
        return $this->belongsTo(AiDiagnosis::class);
    }
}
