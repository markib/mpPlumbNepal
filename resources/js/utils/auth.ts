import { apiUrl } from './api';
import type { AuthUser, UserRole } from '../types';

const storageKey = 'plumbnepal_api_token';

export const getAuthToken = () => localStorage.getItem(storageKey);
export const setAuthToken = (token: string) => localStorage.setItem(storageKey, token);
export const clearAuthToken = () => localStorage.removeItem(storageKey);

export const authHeaders = () => {
  const token = getAuthToken();
  return {
    Accept: 'application/json',
    'Content-Type': 'application/json',
    ...(token ? { Authorization: `Bearer ${token}` } : {}),
  };
};

export const fetchMe = async (): Promise<AuthUser | null> => {
  const token = getAuthToken();
  if (!token) {
    return null;
  }

  const response = await fetch(apiUrl('/api/v1/me'), {
    headers: authHeaders(),
  });

  if (!response.ok) {
    clearAuthToken();
    return null;
  }

  return response.json();
};

export const availableRoles: UserRole[] = [
  'customer',
  'admin',
  'service_provider',
  'plumber',
  'shop_keeper',
];
