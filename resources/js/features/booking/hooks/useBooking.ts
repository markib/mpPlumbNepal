import { useState } from 'react';

import { createBooking } from '../services/bookingApi';

import { BookingFormValues } from '../types/booking';

export const useBooking = () => {
    const [loading, setLoading] = useState(false);

    const submitBooking = async (
        payload: BookingFormValues
    ) => {
        setLoading(true);

        try {
            return await createBooking(payload);
        } finally {
            setLoading(false);
        }
    };

    return {
        loading,
        submitBooking,
    };
};