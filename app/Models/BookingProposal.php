<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingProposal extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'plumber_profile_id',
        'base_fee',
        'material_cost',
        'eta_minutes',
        'proposal_terms',
        'status',
    ];

    protected $casts = [
        'proposal_terms' => 'array',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function plumber()
    {
        return $this->belongsTo(PlumberProfile::class, 'plumber_profile_id');
    }
}
