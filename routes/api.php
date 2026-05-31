<?php

use App\Http\Controllers\Api\AiController;
use App\Http\Controllers\Api\AiPipelineController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\BookingProposalController;
use App\Http\Controllers\Api\DispatchController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ServiceTypeController;
use App\Http\Controllers\Api\VerificationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | PUBLIC ROUTES
    |--------------------------------------------------------------------------
    */

    Route::post('auth/register', [AuthController::class, 'register']);
    Route::post('auth/login', [AuthController::class, 'login']);

    Route::get('service-types', [ServiceTypeController::class, 'index']);

    Route::post('bookings', [BookingController::class, 'createBooking']);

    /*
    |--------------------------------------------------------------------------
    | PUBLIC TRACKING ROUTE
    |--------------------------------------------------------------------------
    */

    Route::get(
        'bookings/{booking}/plumber-location',
        [DispatchController::class, 'getPlumberLocation']
    );

    /*
    |--------------------------------------------------------------------------
    | AUTHENTICATED ROUTES
    |--------------------------------------------------------------------------
    */

    Route::middleware('auth:sanctum')->group(function () {

        Route::get('me', [AuthController::class, 'me']);

        Route::post('auth/logout', [AuthController::class, 'logout']);

        Route::post('ai/diagnose', [AiController::class, 'diagnose']);

        /*
        |--------------------------------------------------------------------------
        | DISPATCH
        |--------------------------------------------------------------------------
        */

        Route::get(
            'dispatch/search',
            [DispatchController::class, 'search']
        );

        Route::get(
            'bookings/{booking}/nearby-plumbers',
            [DispatchController::class, 'searchBooking']
        );

        Route::post(
            'dispatch/availability',
            [DispatchController::class, 'updateAvailability']
        );

        Route::post(
            'dispatch/location',
            [DispatchController::class, 'updateLocation']
        );

        Route::post(
            'dispatch/agent-search',
            [DispatchController::class, 'agentSearch']
        );

        Route::get(
            'bookings/{booking}/dispatch-recommendations',
            [DispatchController::class, 'dispatchRecommendations']
        );

        /*
        |--------------------------------------------------------------------------
        | BOOKINGS
        |--------------------------------------------------------------------------
        */

        Route::get(
            'bookings/{booking}',
            [BookingController::class, 'show']
        );

        Route::patch(
            'bookings/{booking}/status',
            [BookingController::class, 'updateStatus']
        );

        Route::get(
            'bookings/{booking}/track',
            [BookingController::class, 'track']
        );

        Route::post(
            'bookings/{booking}/invite-plumber',
            [BookingController::class, 'invitePlumber']
        );

        Route::get(
            'bookings/{booking}/broadcast-status',
            [BookingController::class, 'broadcastStatus']
        );

        Route::post(
            'bookings/{booking}/accept',
            [DispatchController::class, 'acceptBooking']
        );

        Route::post(
            'bookings/{booking}/reject',
            [DispatchController::class, 'rejectBooking']
        );

        Route::get(
            'bookings/{booking}/nearby-plumbers',
            [DispatchController::class, 'getNearbyPlumbers']
        );

        /*
        |--------------------------------------------------------------------------
        | PROPOSALS
        |--------------------------------------------------------------------------
        */

        Route::get(
            'plumber/open-requests',
            [BookingProposalController::class, 'openRequests']
        );

        Route::get(
            'plumber/assigned-jobs',
            [BookingProposalController::class, 'assignedJobs']
        );

        Route::post(
            'bookings/{booking}/proposals',
            [BookingProposalController::class, 'store']
        );

        Route::get(
            'customer/proposals',
            [BookingProposalController::class, 'customerProposals']
        );

        Route::get(
            'customer/job-orders',
            [BookingProposalController::class, 'customerJobOrders']
        );

        Route::get(
            'customer/pending-requests',
            [BookingProposalController::class, 'customerPendingRequests']
        );

        Route::post(
            'bookings/{booking}/proposals/{proposal}/accept',
            [BookingProposalController::class, 'accept']
        );

        Route::post(
            'bookings/{booking}/start-job',
            [BookingProposalController::class, 'startJob']
        );

        Route::post(
            'bookings/{booking}/complete-job',
            [BookingProposalController::class, 'completeJob']
        );

        /*
        |--------------------------------------------------------------------------
        | VERIFICATION
        |--------------------------------------------------------------------------
        */

        Route::post(
            'verification/upload',
            [VerificationController::class, 'uploadDocument']
        );

        Route::post(
            'verification/submit',
            [VerificationController::class, 'submitForReview']
        );

        Route::get(
            'verification/status',
            [VerificationController::class, 'status']
        );

        /*
        |--------------------------------------------------------------------------
        | PAYMENTS
        |--------------------------------------------------------------------------
        */

        Route::post(
            'payments/initiate',
            [PaymentController::class, 'initiate']
        );

        Route::post(
            'payments/callback',
            [PaymentController::class, 'callback']
        );

        /*
        |--------------------------------------------------------------------------
        | BROADCASTING AUTHENTICATION
        |--------------------------------------------------------------------------
        | Placing this inside the Sanctum group ensures that incoming Echo requests
        | inherit the authentication guard context seamlessly.
        */
        Route::post('broadcasting/auth', function (Request $request) {
            Log::info('Broadcasting auth request', [
                'user_id' => $request->user()?->id,
                'role' => $request->user()?->role,
                'channel_name' => $request->input('channel_name'),
                'socket_id' => $request->input('socket_id'),
            ]);

            return Broadcast::auth($request);
        });

        Route::post('/pipeline/start', [
            AiPipelineController::class,
            'start',
        ]);
        Route::get('/pipeline/{pipelineId}', [AiPipelineController::class, 'show']);
    });

});
