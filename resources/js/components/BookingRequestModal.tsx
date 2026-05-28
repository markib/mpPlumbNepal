import React, { useState, useEffect, useCallback } from 'react';
import type { BookingBroadcast } from '../features/booking/types/booking';
import { apiUrl } from '../utils/api';
import { getAuthToken } from '../utils/auth';

interface BookingRequestModalProps {
    booking: BookingBroadcast;
    plumberId: number;
    onClose: () => void;
    onAccepted?: (bookingId: number) => void;
    onRejected?: (bookingId: number) => void;
}

interface BookingRequestModalState {
    status: 'pending' | 'accepting' | 'rejecting' | 'accepted' | 'rejected' | 'taken' | 'expired';
    error: string | null;
}

export const BookingRequestModal: React.FC<BookingRequestModalProps> = ({
    booking,
    plumberId,
    onClose,
    onAccepted,
    onRejected,
}) => {
    const [state, setState] = useState<BookingRequestModalState>({
        status: 'pending',
        error: null,
    });
    const [timeRemaining, setTimeRemaining] = useState<number>(0);

    useEffect(() => {
        if (!booking.expires_at) {
            return;
        }

        const calculateRemaining = () => {
            const expiresAt = new Date(booking.expires_at).getTime();
            const now = Date.now();
            const remaining = Math.max(0, Math.floor((expiresAt - now) / 1000));
            return remaining;
        };

        setTimeRemaining(calculateRemaining());

        const interval = setInterval(() => {
            const remaining = calculateRemaining();
            setTimeRemaining(remaining);

            if (remaining <= 0 && state.status === 'pending') {
                setState((prev) => ({ ...prev, status: 'expired' }));
            }
        }, 1000);

        return () => clearInterval(interval);
    }, [booking.expires_at, state.status]);

    const handleAccept = useCallback(async () => {
        if (state.status !== 'pending') {
            return;
        }

        setState({ status: 'accepting', error: null });

        try {
            const token = getAuthToken();
            const response = await fetch(apiUrl(`/api/v1/bookings/${booking.id}/accept`), {
                method: 'POST',
                headers: {
                    Authorization: `Bearer ${token}`,
                    'Content-Type': 'application/json',
                },
            });

            const data = await response.json();

            if (!response.ok) {
                if (data.message?.includes('already assigned')) {
                    setState({ status: 'taken', error: 'This booking was accepted by another plumber' });
                } else {
                    setState({ status: 'pending', error: data.message || 'Failed to accept booking' });
                }
                return;
            }

            setState({ status: 'accepted', error: null });
            onAccepted?.(booking.id);
        } catch (error) {
            setState({ status: 'pending', error: 'Network error. Please try again.' });
        }
    }, [booking.id, state.status, onAccepted]);

    const handleReject = useCallback(async () => {
        if (state.status !== 'pending') {
            return;
        }

        setState({ status: 'rejecting', error: null });

        try {
            const token = getAuthToken();
            await fetch(apiUrl(`/api/v1/bookings/${booking.id}/reject`), {
                method: 'POST',
                headers: {
                    Authorization: `Bearer ${token}`,
                    'Content-Type': 'application/json',
                },
            });

            setState({ status: 'rejected', error: null });
            onRejected?.(booking.id);
            onClose();
        } catch (error) {
            setState({ status: 'pending', error: 'Failed to reject booking' });
        }
    }, [booking.id, state.status, onRejected, onClose]);

    const formatTime = (seconds: number): string => {
        const mins = Math.floor(seconds / 60);
        const secs = seconds % 60;
        return `${mins}:${secs.toString().padStart(2, '0')}`;
    };

    const getStatusMessage = (): string => {
        switch (state.status) {
            case 'accepting':
                return 'Accepting booking...';
            case 'rejecting':
                return 'Rejecting...';
            case 'accepted':
                return 'Booking accepted!';
            case 'rejected':
                return 'Booking rejected';
            case 'taken':
                return 'Booking taken by another plumber';
            case 'expired':
                return 'This booking has expired';
            default:
                return '';
        }
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div className="w-full max-w-md rounded-2xl bg-white shadow-2xl overflow-hidden">
                {/* Header */}
                <div className={`p-4 ${booking.is_emergency ? 'bg-red-500' : 'bg-blue-500'} text-white`}>
                    <div className="flex items-center justify-between">
                        <h3 className="text-lg font-semibold">New Booking Request</h3>
                        {state.status === 'pending' && timeRemaining > 0 && (
                            <span className="text-sm font-mono bg-white/20 px-2 py-1 rounded">
                                {formatTime(timeRemaining)}
                            </span>
                        )}
                    </div>
                    {booking.is_emergency && (
                        <p className="mt-1 text-sm font-medium">EMERGENCY SERVICE</p>
                    )}
                </div>

                {/* Content */}
                <div className="p-5 space-y-4">
                    {/* Customer Info */}
                    <div className="flex items-center gap-3">
                        <div className="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center">
                            <svg className="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <div>
                            <p className="font-medium text-gray-900">{booking.customer_name}</p>
                            <p className="text-sm text-gray-500">{booking.service_type}</p>
                        </div>
                    </div>

                    {/* Location & Distance */}
                    <div className="flex items-center gap-3 p-3 bg-gray-50 rounded-xl">
                        <svg className="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <div className="flex-1">
                            <p className="text-sm text-gray-500">Distance</p>
                            <p className="font-semibold">{booking.distance_km} km</p>
                        </div>
                        <div className="flex-1">
                            <p className="text-sm text-gray-500">ETA</p>
                            <p className="font-semibold">~{booking.eta_minutes} min</p>
                        </div>
                    </div>

                    {/* Amount */}
                    <div className="flex items-center justify-between p-3 bg-green-50 rounded-xl">
                        <span className="text-gray-600">Service Amount</span>
                        <span className="text-xl font-bold text-green-600">NPR {booking.amount.toLocaleString()}</span>
                    </div>

                    {/* Skills Required */}
                    {booking.skill_required && booking.skill_required.length > 0 && (
                        <div>
                            <p className="text-sm text-gray-500 mb-2">Skills Required</p>
                            <div className="flex flex-wrap gap-2">
                                {booking.skill_required.map((skill, index) => (
                                    <span
                                        key={index}
                                        className="px-3 py-1 bg-amber-100 text-amber-700 text-sm rounded-full"
                                    >
                                        {skill}
                                    </span>
                                ))}
                            </div>
                        </div>
                    )}

                    {/* Status Message */}
                    {state.error && (
                        <div className="p-3 bg-red-50 border border-red-200 rounded-xl">
                            <p className="text-sm text-red-600">{state.error}</p>
                        </div>
                    )}

                    {state.status !== 'pending' && (
                        <div className={`p-3 rounded-xl ${
                            state.status === 'accepted' ? 'bg-green-50 border border-green-200' :
                            state.status === 'taken' || state.status === 'expired' ? 'bg-gray-50 border border-gray-200' :
                            'bg-blue-50 border border-blue-200'
                        }`}>
                            <p className={`text-sm font-medium ${
                                state.status === 'accepted' ? 'text-green-600' :
                                state.status === 'taken' || state.status === 'expired' ? 'text-gray-600' :
                                'text-blue-600'
                            }`}>
                                {getStatusMessage()}
                            </p>
                        </div>
                    )}
                </div>

                {/* Footer Actions */}
                <div className="p-4 bg-gray-50 flex gap-3">
                    {state.status === 'pending' && (
                        <>
                            <button
                                onClick={handleReject}
                                disabled={state.status === 'rejecting'}
                                className="flex-1 px-4 py-3 border border-gray-300 text-gray-700 font-medium rounded-xl hover:bg-gray-100 transition-colors disabled:opacity-50"
                            >
                                {state.status === 'rejecting' ? 'Rejecting...' : 'Reject'}
                            </button>
                            <button
                                onClick={handleAccept}
                                disabled={state.status === 'accepting'}
                                className="flex-1 px-4 py-3 bg-blue-600 text-white font-medium rounded-xl hover:bg-blue-700 transition-colors disabled:opacity-50"
                            >
                                {state.status === 'accepting' ? 'Accepting...' : 'Accept'}
                            </button>
                        </>
                    )}

                    {(state.status === 'accepted' || state.status === 'rejected' || state.status === 'taken' || state.status === 'expired') && (
                        <button
                            onClick={onClose}
                            className="w-full px-4 py-3 bg-gray-200 text-gray-800 font-medium rounded-xl hover:bg-gray-300 transition-colors"
                        >
                            Close
                        </button>
                    )}
                </div>
            </div>
        </div>
    );
};

export default BookingRequestModal;