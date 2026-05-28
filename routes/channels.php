<?php

use App\Models\AiPipeline;
use Illuminate\Support\Facades\Broadcast;
use App\Models\User;
use App\Models\Booking;
use Illuminate\Support\Facades\Log;

/*
|--------------------------------------------------------------------------
| Booking Channel
|--------------------------------------------------------------------------
*/

Broadcast::channel('bookings.{bookingId}', function (User $user, $bookingId) {
    Log::info('Broadcast auth', [
        'user_id' => $user->id,
        'role' => $user->role,
        'booking_id' => $bookingId,
    ]);
    $booking = Booking::find($bookingId);

    if (!$booking) {
        return false;
    }

    if ($user->role === 'admin') {
        return true;
    }

    // Cast IDs to integers to ensure safe strict comparisons
    if ($user->role === 'customer' && (int) $booking->user_id === (int) $user->id) {
        return true;
    }

    if ($user->role === 'plumber') {
        $profile = $user->plumberProfile;
        if ($profile && (
            (int) $booking->accepted_by_id === (int) $profile->id ||
            (int) $booking->plumber_profile_id === (int) $profile->id
        )) {
            return true;
        }
    }

    return false;
});

/*
|--------------------------------------------------------------------------
| Available Plumbers (Presence/Internal Channel)
|--------------------------------------------------------------------------
*/
Broadcast::channel('plumbers.available', function (User $user) {
    if ($user->role !== 'plumber') {
        return false;
    }

    $profile = $user->plumberProfile;

    return $profile && $profile->is_online && $profile->is_available;
});

/*
|--------------------------------------------------------------------------
| Plumber Live Location Tracking Channel
|--------------------------------------------------------------------------
*/
Broadcast::channel('plumbers.{plumberId}.location', function (User $user, $plumberId) {
    // 1. Always allow the plumber themselves to connect/broadcast
    if ($user->role === 'plumber') {
        $profile = $user->plumberProfile;
        if ($profile && (int) $profile->id === (int) $plumberId) {
            return true;
        }
    }

    // 2. Allow a customer to listen to this location ONLY if this specific plumber is assigned to their active booking
    if ($user->role === 'customer') {
        return Booking::where('user_id', $user->id)
            ->where(function ($query) use ($plumberId) {
                $query->where('accepted_by_id', $plumberId)
                    ->orWhere('plumber_profile_id', $plumberId);
            })
            ->whereIn('workflow_status', ['contracted', 'in_progress'])
            ->exists();
    }

    // 3. Allow admins to track
    return $user->role === 'admin';
});

/*
|--------------------------------------------------------------------------
| Individual Plumber Private Channel
|--------------------------------------------------------------------------
*/
Broadcast::channel('plumbers.{plumberId}', function (User $user, $plumberId) {
    $profile = $user->plumberProfile;

    Log::info('CHANNEL AUTH', [
        'user_id' => $user->id,
        'role' => $user->role,
        'relationship_profile_id' => $profile?->id,
        'requested_channel' => $plumberId,
    ]);

    if ($user->role !== 'plumber') {
        return false;
    }

    return $profile && (int) $profile->id === (int) $plumberId;
});

Broadcast::channel('plumber.{plumberId}', function (User $user, $plumberId) {
    if ($user->role !== 'plumber') {
        return false;
    }

    $profile = $user->plumberProfile;

    return $profile && (int) $profile->id === (int) $plumberId;
});

/*
|--------------------------------------------------------------------------
| Standard Generic User Channel
|--------------------------------------------------------------------------
*/
Broadcast::channel('user.{userId}', function (User $user, $userId) {
    return (int) $user->id === (int) $userId;
});


Broadcast::channel('pipeline.{pipelineId}', function (
    User $user,
    $pipelineId
) {

  
    $pipeline = AiPipeline::find($pipelineId);

    Log::info('Broadcasting auth request inside channels', [
        'user_id' => $user->id,
        'pipeline_id' => $pipelineId,
        'pipeline_user_id' => $pipeline?->user_id,
    ]);

    if (!$pipeline) {
        return false;
    }

    return (int) $user->id === (int) $pipeline->user_id;
});