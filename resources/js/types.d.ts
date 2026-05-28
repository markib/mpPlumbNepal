export type UserRole = 'customer' | 'admin' | 'service_provider' | 'plumber' | 'shop_keeper';

export interface AuthUser {
  id: number;
  name: string;
  email: string;
  phone: string;
  role: UserRole;
  locale: 'en' | 'ne';
  location?: {
    latitude: number;
    longitude: number;
    description?: string;
    address?: string;
  };
}

// export interface BookingFormValues {
//   latitude: number;
//   longitude: number;
//   landmark?: string;
//   ward_number?: string;
//   tole_name?: string;
//   service_type_id: number;
//   service_notes?: string;
//   is_emergency: boolean;
//   payment_method: 'esewa' | 'khalti' | 'ime_pay' | 'cod';
//   ai_diagnosis_id?: number;
// }
