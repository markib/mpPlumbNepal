import { apiUrl } from '../../../utils/api';
import { authHeaders } from '../../../utils/auth';
import { BookingFormValues } from '../types/booking';

export const createBooking = async (
    payload: BookingFormValues
) => {
    const response = await fetch(apiUrl('/api/v1/bookings'), {
        method: 'POST',
        credentials: 'include',
        headers: authHeaders(),
        body: JSON.stringify(payload),
    });

    if (!response.ok) {
        const contentType =
            response.headers.get('content-type') ?? '';

        if (contentType.includes('application/json')) {
            const data = await response.json();
            const validationErrors = data?.errors
                ? Object.values(data.errors)
                    .flat()
                    .filter(Boolean)
                    .join(' ')
                : '';

            throw new Error(
                validationErrors ||
                    data?.message ||
                    'Booking request failed'
            );
        }

        throw new Error('Booking request failed');
    }

    return response.json();
};

export const fetchPlumberLocation = async (
    bookingId: number
) => {
    const response = await fetch(
        apiUrl(
            `/api/v1/bookings/${bookingId}/plumber-location`
        ),
        {
            credentials: 'include',
            headers: {
                Accept: 'application/json',
            },
        }
    );

    if (!response.ok) {
        throw new Error('Unable to fetch location');
    }

    const data = await response.json();

    return {
        ok: response.ok,
        status: response.status,
        data,
    };
};

export const fetchServiceTypes = async () => {
    const response = await fetch(
        apiUrl('/api/v1/service-types')
    );

    if (!response.ok) {
        throw new Error('Unable to fetch services');
    }

    return response.json();
};
