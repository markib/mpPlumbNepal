<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function initiate(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|integer|exists:bookings,id',
            'gateway' => 'required|in:esewa,khalti,ime_pay,cod',
        ]);

        $booking = Booking::findOrFail($request->input('booking_id'));

        $payment = Payment::create([
            'booking_id' => $booking->id,
            'amount' => $booking->amount,
            'gateway' => $request->input('gateway'),
            'status' => $request->input('gateway') === 'cod' ? 'pending' : 'initiated',
        ]);

        $response = [
            'payment_id' => $payment->id,
            'status' => $payment->status,
            'gateway' => $payment->gateway,
        ];

        if ($payment->gateway !== 'cod') {
            $response['redirect_url'] = route('payment.gateway.redirect', ['payment' => $payment->id]);
        }

        return response()->json($response, 201);
    }

    public function callback(Request $request)
    {
        $request->validate([
            'payment_id' => 'required|integer|exists:payments,id',
            'gateway' => 'required|in:esewa,khalti,ime_pay',
            'status' => 'required|in:success,failed',
            'transaction_reference' => 'nullable|string|max:255',
        ]);

        $payment = Payment::findOrFail($request->input('payment_id'));
        $payment->status = $request->input('status') === 'success' ? 'completed' : 'failed';
        $payment->transaction_reference = $request->input('transaction_reference');
        $payment->save();

        if ($payment->status === 'completed') {
            $payment->booking->status_id = 1; // Continue with normal booking lifecycle
            $payment->booking->save();
        }

        Log::info('Payment callback received', $request->all());

        return response()->json([ 'payment' => $payment ]);
    }
}
