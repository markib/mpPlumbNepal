import React, { useEffect, useState } from 'react';

import BookingForm from '../components/BookingForm';
import NearbyPlumbersList from '../components/NearbyPlumbersList';
import PlumberTrackingCard from '../components/PlumberTrackingCard';

import { useBooking } from '../hooks/useBooking';
import { useServiceTypes } from '../hooks/useServiceTypes';
import BookingNotification from '../components/BookingNotification';

import type {
    BookingFormValues,
    NearbyPlumber,
} from '../types/booking';

import type {
    AiDiagnosisResult,
} from '../../ai/types/ai';

const DEFAULT_BOOKING: BookingFormValues = {
    latitude: 27.7172,
    longitude: 85.3240,

    service_type_id: 1,

    ward_number: '',
    tole_name: '',
    landmark: '',

    payment_method: 'cod',

    service_notes: '',

    is_emergency: false,

    ai_diagnosis_id: undefined,

    image: null,
};

const BookingPage: React.FC = () => {
    const {
        loading,
        submitBooking,
    } = useBooking();

    const {
        serviceTypes = [],
        loading: isLoadingTypes,
    } = useServiceTypes();

    const [bookingId, setBookingId] =
        useState<number | undefined>();

    const [
        nearbyPlumbers,
        setNearbyPlumbers,
    ] = useState<NearbyPlumber[]>([]);

    const [booking, setBooking] =
        useState<BookingFormValues>(
            DEFAULT_BOOKING
        );

    const [notification, setNotification] = useState<{
        type: 'success' | 'error' | 'info';
        message: string;
    } | null>(null);

    useEffect(() => {
        if (!notification) {
            return;
        }

        const timeout = window.setTimeout(() => {
            setNotification(null);
        }, 5000);

        return () => {
            window.clearTimeout(timeout);
        };
    }, [notification]);

    /*
    |--------------------------------------------------------------------------
    | SET DEFAULT SERVICE TYPE
    |--------------------------------------------------------------------------
    */
    useEffect(() => {
        if (
            serviceTypes.length > 0 &&
            !booking.service_type_id
        ) {
            setBooking((prev) => ({
                ...prev,
                service_type_id:
                    prev.service_type_id ?? serviceTypes[0].id,
            }));
        }
    }, [serviceTypes]);

    /*
    |--------------------------------------------------------------------------
    | AI RESULT HANDLER
    |--------------------------------------------------------------------------
    */
    const handleAiComplete = (
        result: AiDiagnosisResult | null
    ) => {
        if (!result) {
            setBooking((prev) => ({
                ...prev,
                service_notes: '',
                ai_diagnosis_id:
                    undefined,
            }));

            return;
        }

        setBooking((prev) => ({
            ...prev,

            service_notes:
                result.summary ?? '',

            is_emergency:
                result.urgency
                    ?.toLowerCase() ===
                'high' ||
                result.urgency
                    ?.toLowerCase() ===
                'emergency',

            ai_diagnosis_id:
                result.ai_diagnosis_id,
        }));
    };

    /*
    |--------------------------------------------------------------------------
    | SUBMIT BOOKING
    |--------------------------------------------------------------------------
    */
    const handleSubmit = async (
        e: React.FormEvent<HTMLFormElement>
    ) => {
        e.preventDefault();

        try {
            const data =
                await submitBooking(
                    booking
                );

            setNotification({
                type: 'success',
                message:
                    data?.message ??
                    'Request submitted. Nearby plumbers can now send quotes.',
            });


            /*
            |--------------------------------------------------------------------------
            | SET NEARBY PLUMBERS
            |--------------------------------------------------------------------------
            */
            setNearbyPlumbers(
                Array.isArray(
                    data?.nearby_plumbers
                )
                    ? data.nearby_plumbers
                    : []
            );

            /*
            |--------------------------------------------------------------------------
            | RESET FORM
            |--------------------------------------------------------------------------
            */
            setBooking({
                ...DEFAULT_BOOKING,

                service_type_id:
                    serviceTypes?.[0]
                        ?.id ?? 1,
            });
        } catch (error) {
            
            console.error(
                'Booking failed:',
                error
            );

            setNotification({
                type: 'error',
                message:
                    error instanceof Error
                        ? error.message
                        : 'Failed to submit booking. Try again.',
            });
        }
    };

    return (
        <div className="mx-auto max-w-5xl space-y-6 p-6">
          
            {notification && (
                <BookingNotification
                    type={notification.type}
                    message={notification.message}
                    onDismiss={() =>
                        setNotification(null)
                    }
                />
            )}
            {/* BOOKING FORM */}
            <BookingForm
                booking={booking}
                setBooking={setBooking}
                serviceTypes={
                    serviceTypes
                }
                isLoadingTypes={
                    isLoadingTypes
                }
                isSubmitting={loading}
                onAiComplete={
                    handleAiComplete
                }
                onSubmit={handleSubmit}
            />

            {/* NEARBY PLUMBERS */}
            {nearbyPlumbers.length >
                0 && (
                    <NearbyPlumbersList
                        plumbers={
                            nearbyPlumbers
                        }
                    />
                )}

            {/* PLUMBER PROFILE CARD */}
            {nearbyPlumbers.length > 0 && (
                <PlumberTrackingCard
                    plumber={{
                        ...nearbyPlumbers[0],
                        is_verified: true,
                        distance_text: nearbyPlumbers[0].distance_meters
                            ? `${(nearbyPlumbers[0].distance_meters / 1000).toFixed(1)} km`
                            : undefined,
                        eta_minutes: Math.floor(Math.random() * 15) + 5,
                    }}
                />
            )}
        </div>
    );
};

export default BookingPage;
