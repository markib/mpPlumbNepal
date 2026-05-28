import { useEffect, useRef, useState } from 'react';

import { fetchPlumberLocation } from '../services/bookingApi';

export const usePlumberTracking = (
    bookingId?: number
) => {
    const [location, setLocation] = useState(null);

    const intervalRef = useRef<NodeJS.Timeout | null>(null);

    useEffect(() => {
        if (!bookingId) return;

        load();

        intervalRef.current = setInterval(load, 30000);

        return () => {
            if (intervalRef.current) {
                clearInterval(intervalRef.current);
            }
        };
    }, [bookingId]);

    const load = async () => {
        if (!bookingId) return;

        try {
            const res = await fetchPlumberLocation(
                bookingId
            );
            const data = await res.data;
            setLocation(data.location ?? null);
        } catch (e) {
            setLocation(null);
        }
    };

    return {
        location,
    };
};
