import { useEffect, useState } from 'react';
import { fetchServiceTypes } from '../services/bookingApi';
import type { ServiceTypeOption } from '../types/booking';

export const useServiceTypes = () => {
    const [serviceTypes, setServiceTypes] = useState<ServiceTypeOption[]>([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        load();
    }, []);

    const load = async () => {
        try {
            const data = await fetchServiceTypes();
            setServiceTypes(data);
        } finally {
            setLoading(false);
        }
    };

    return {
        serviceTypes,
        loading,
    };
};