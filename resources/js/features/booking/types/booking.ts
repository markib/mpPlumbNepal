export interface BookingFormValues {
    latitude: number;
    longitude: number;

    service_type_id: number;

    ward_number: string;
    tole_name: string;
    landmark: string;

    payment_method: 'cod' | 'esewa' | 'khalti';

    service_notes?: string;

    is_emergency: boolean;

    ai_diagnosis_id?: number;

    image?: File | null;
}

export interface NearbyPlumber {
    id: number;
    rating?: number;
    distance_meters?: number;
    distance_km?: number;
    eta_minutes?: number;
    is_online?: boolean;
    is_available?: boolean;
    is_verified?: boolean;
    socket_id?: string | null;
    skills?: string[];

    user?: {
        name?: string;
        phone?: string;
    };
}

export interface BookingBroadcast {
    id: number;
    customer_name: string;
    customer_phone?: string;
    service_type: string;
    skill_required: string[];
    latitude: number;
    longitude: number;
    distance_km: number;
    eta_minutes: number;
    is_emergency: boolean;
    amount: number;
    created_at: string;
    expires_at: string;
    min_rating_required: number;
}

export interface BroadcastStatus {
    broadcast_status: 'pending' | 'broadcasting' | 'assigned' | 'expired' | 'no_plumbers' | 'failed';
    expires_at: string | null;
    expires_in_seconds: number | null;
    accepted_by: {
        id: number;
        name: string;
        phone: string;
    } | null;
}

export interface PlumberLocation {
    latitude: number;
    longitude: number;

    accuracy?: number;
    speed?: number;
    heading?: number;

    updated_at: string;
}

export interface ServiceTypeOption {
    id: number;
    name: string;
}

export interface AiDiagnosisResult {
    issue_type: string;
    urgency: string;
    estimated_price_min: number;
    estimated_price_max: number;
    recommended_service: string;
    confidence: number;
    summary: string;
    ai_diagnosis_id?: number;
}
