export const apiBaseUrl = import.meta.env.VITE_APP_URL || 'http://localhost:8000';

export const apiUrl = (path: string) => {
  const normalizedPath = path.startsWith('/') ? path : `/${path}`;
  return `${apiBaseUrl}${normalizedPath}`;
};
