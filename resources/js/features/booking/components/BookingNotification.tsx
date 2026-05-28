interface Props {
    type: 'success' | 'error' | 'info';
    message: string;
    onDismiss: () => void;
}

const BookingNotification = ({
    type,
    message,
    onDismiss,
}: Props) => {
    const styles = {
        success: 'border-emerald-200 bg-emerald-50 text-emerald-800',
        error: 'border-rose-200 bg-rose-50 text-rose-800',
        info: 'border-cyan-200 bg-cyan-50 text-cyan-800',
    }[type];

    return (
        <div
            role={type === 'error' ? 'alert' : 'status'}
            aria-live={type === 'error' ? 'assertive' : 'polite'}
            className={`fixed right-4 top-4 z-50 flex w-[calc(100%-2rem)] max-w-sm items-start gap-3 rounded-lg border p-4 shadow-lg ${styles}`}
        >
            <p className="flex-1 text-sm font-medium leading-5">
                {message}
            </p>

            <button
                type="button"
                onClick={onDismiss}
                aria-label="Dismiss notification"
                className="shrink-0 rounded-md border-0 bg-transparent p-1 text-current hover:bg-black/5 focus:ring-2 focus:ring-current"
            >
                x
            </button>
        </div>
    );
};

export default BookingNotification;
