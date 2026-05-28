import type { BookingBroadcast } from '../features/booking/types/booking';

type EchoCallback = (data: any) => void;

interface BookingCallbacks {
    onAccepted?: (data: { booking: any }) => void;
    onExpired?: () => void;
    onAssigned?: (data: { booking: any }) => void;
}

class RealtimeService {
    private echo: any = null;
    private isConnected: boolean = false;
    private bookingSubscriptions: Map<number, any> = new Map();
    private plumberSubscription: any = null;
    private locationSubscription: any = null;
    private plumberId: number = 0;

    private readonly STORAGE_KEY = 'plumbnepal_plumber_id';

    async connect(token: string): Promise<void> {
        if (this.isConnected && this.echo) {
            return;
        }

        try {
            this.plumberId = this.plumberId || this.getStoredPlumberId() || this.getPlumberIdFromToken(token);

            // Dynamic import for the bootstrap file
            const { initializeEcho } = await import('../bootstrap/reverb');
            this.echo = initializeEcho(token);

            // Wait for connection to be ready
            await new Promise<void>((resolve) => {
                // Check if already connected
                if (this.echo.connector && typeof this.echo.connector.connect === 'function') {
                    this.echo.connector.connect();
                }

                // Use a simple check with timeout as fallback
                setTimeout(() => {
                    this.isConnected = true;
                    console.log('Reverb realtime service connected');
                    resolve();
                }, 1000);

                // Timeout fallback
                setTimeout(() => {
                    if (!this.isConnected) {
                        console.warn('Reverb connection timeout - continuing anyway');
                        this.isConnected = true;
                        resolve();
                    }
                }, 5000);
            });
        } catch (error) {
            console.error('Failed to connect to realtime service:', error);
            throw error;
        }
    }

    setPlumberId(id: number): void {
        this.plumberId = id;
        localStorage.setItem(this.STORAGE_KEY, id.toString());
    }

    private getStoredPlumberId(): number {
        const stored = localStorage.getItem(this.STORAGE_KEY);
        return stored ? parseInt(stored, 10) : 0;
    }

    private getPlumberIdFromToken(token: string): number {
        try {
            const payload = JSON.parse(atob(token.split('.')[1]));
            return payload.plumber_id || payload.sub || 0;
        } catch {
            return 0;
        }
    }

    disconnect(): void {
        if (this.echo) {
            this.bookingSubscriptions.forEach((subscription) => {
                subscription.stopListening('BookingBroadcast');
                subscription.stopListening('BookingAssigned');
                subscription.stopListening('BookingExpired');
                subscription.stopListening('BookingAccepted');
            });
            this.bookingSubscriptions.clear();

            if (this.plumberSubscription) {
                this.plumberSubscription.stopListening('BookingBroadcast');
                this.plumberSubscription.stopListening('.booking.proposal.accepted');
            }

            if (this.locationSubscription) {
                this.locationSubscription.stopListening('PlumberLocationUpdated');
            }

            this.echo.disconnect();
            this.echo = null;
            this.isConnected = false;
        }
    }

    listenForBookings(callback: EchoCallback): any {
        console.log('SUBSCRIBING TO BOOKINGS CHANNEL');
        if (!this.echo || !this.plumberId) {
            console.warn('Realtime service not connected or plumber ID not set', { echo: !!this.echo, plumberId: this.plumberId });
            return null;
        }

        const channelName = `plumbers.${this.plumberId}`;
        console.log('Listening on channel:', channelName);

        this.plumberSubscription = this.echo
            .private(channelName)
            .subscribed(() => {
                console.log('Successfully subscribed:', channelName);
            })
            .error((err: any) => {
                console.error('Subscription failed:', err);
            })
            .listen('BookingBroadcast', (event: any) => {
                console.log('BOOKING EVENT RECEIVED:', event);
                callback(event.booking);
            });

        return this.plumberSubscription;
    }

    listenForProposalAccepted(callback: EchoCallback): any {
        if (!this.echo || !this.plumberId) {
            console.warn('Realtime service not connected or plumber ID not set', { echo: !!this.echo, plumberId: this.plumberId });
            return null;
        }

        const channelName = `plumbers.${this.plumberId}`;
        const channel = this.plumberSubscription ?? this.echo.private(channelName);

        this.plumberSubscription = channel.listen('.booking.proposal.accepted', (event: any) => {
            console.log('BookingProposalAccepted received:', event);
            callback(event);
        });

        return this.plumberSubscription;
    }

    listenForBookingUpdates(
        bookingId: number,
        callbacks: BookingCallbacks
    ): any {
        if (!this.echo) {
            console.warn('Realtime service not connected');
            return null;
        }

        const channel = this.echo.private(`bookings.${bookingId}`);

        if (callbacks.onAccepted) {
            channel.listen('BookingAccepted', (event: any) => {
                callbacks.onAccepted?.(event);
            });
        }

        if (callbacks.onAssigned) {
            channel.listen('BookingAssigned', (event: any) => {
                callbacks.onAssigned?.(event);
            });
        }

        if (callbacks.onExpired) {
            channel.listen('BookingExpired', (event: any) => {
                callbacks.onExpired?.();
            });
        }

        this.bookingSubscriptions.set(bookingId, channel);

        return channel;
    }

    listenForPlumberLocation(plumberId: number, callback: EchoCallback): any {
        if (!this.echo) {
            console.warn('Realtime service not connected');
            return null;
        }

        this.locationSubscription = this.echo
            .private(`plumbers.${plumberId}.location`)
            .listen('PlumberLocationUpdated', (event: any) => {
                callback(event);
            });

        return this.locationSubscription;
    }

    unsubscribeFromBooking(bookingId: number): void {
        const subscription = this.bookingSubscriptions.get(bookingId);
        if (subscription) {
            subscription.stopListening('BookingAccepted');
            subscription.stopListening('BookingAssigned');
            subscription.stopListening('BookingExpired');
            this.bookingSubscriptions.delete(bookingId);
        }
    }

    isReady(): boolean {
        return this.isConnected && this.echo !== null;
    }

    getPlumberId(): number {
        return this.plumberId;
    }

    getChannel(channelName: string): any {
        if (!this.echo) {
            return null;
        }
        return this.echo.channel(channelName);
    }

    getPrivateChannel(channelName: string): any {
        if (!this.echo) {
            return null;
        }
        return this.echo.private(channelName);
    }
}

export const realtimeService = new RealtimeService();

export default realtimeService;
