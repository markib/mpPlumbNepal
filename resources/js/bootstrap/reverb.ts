import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

declare global {
    interface Window {
        Echo: Echo<any>;
        Pusher: typeof Pusher;
    }
}

/**
 * Initialize Laravel Echo with Reverb configuration
 * Optimized for Laravel Sanctum & local XAMPP environment
 */
export function initializeEcho(authToken?: string): Echo<any> {
    window.Pusher = Pusher;

    // Fallback constants pointing to your API server config
    const host = import.meta.env.VITE_REVERB_HOST || '127.0.0.1';
    const port = parseInt(import.meta.env.VITE_REVERB_PORT || '8080', 10);
    const backendUrl = import.meta.env.VITE_API_URL || 'http://localhost:8000';

    const echo = new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY || 'abcd1234567890abcd1234567890abcd',
        wsHost: host,
        wsPort: port,
        wssPort: port,
        forceTLS: false,
        enabledTransports: ['ws', 'wss'],

        // Sanctum API Authentication Endpoint Mapping
        authEndpoint: `${backendUrl}/api/v1/broadcasting/auth`,
        auth: {
            headers: {
                // Sanctum looks specifically for the Bearer prefix
                Authorization: authToken ? `Bearer ${authToken}` : '',
                Accept: 'application/json',
            },
        },
    });

    window.Echo = echo;

    return echo;
}

/**
 * Disconnect Echo instance safely
 */
export function disconnectEcho(): void {
    if (window.Echo) {
        window.Echo.disconnect();
        window.Echo = null as any;
    }
}

export default { initializeEcho, disconnectEcho };
