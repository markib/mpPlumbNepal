interface PlumberProfile {
    id: number;
    rating?: number;
    is_verified?: boolean;
    distance_meters?: number;
    is_online?: boolean;
    distance_text?: string;
    eta_minutes?: number;
    user?: {
        name?: string;
        phone?: string;
    };
}

interface Props {
    plumber: PlumberProfile;
}

const PlumberTrackingCard = ({
    plumber,
}: Props) => {
    if (!plumber) return null;

    const rating = plumber.rating ?? 0;
    const stars = Array.from({ length: 5 }, (_, i) => i < Math.round(rating));

    return (
        <div className="rounded-2xl border border-cyan-100 bg-white p-5 shadow-sm">
            <div className="flex items-start gap-4">
                <div className="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-cyan-400 to-cyan-600 text-xl font-bold text-white">
                    {plumber.user?.name?.charAt(0).toUpperCase() ?? 'P'}
                </div>

                <div className="min-w-0 flex-1">
                    <div className="flex items-center gap-2">
                        <h3 className="truncate text-lg font-semibold text-slate-900">
                            {plumber.user?.name ?? 'Plumber'}
                        </h3>
                        {plumber.is_verified && (
                            <span className="inline-flex shrink-0 items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">
                                <svg className="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                                </svg>
                                Verified
                            </span>
                        )}
                    </div>

                    <div className="mt-1 flex items-center gap-3">
                        <div className="flex items-center gap-0.5">
                            {stars.map((filled, i) => (
                                <svg
                                    key={i}
                                    className={`h-4 w-4 ${filled ? 'text-amber-400' : 'text-slate-300'}`}
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                            ))}
                            <span className="ml-1 text-sm font-medium text-slate-700">
                                {rating > 0 ? rating.toFixed(1) : 'New'}
                            </span>
                        </div>

                        {plumber.distance_text && (
                            <span className="text-sm text-slate-500">
                                {plumber.distance_text}
                            </span>
                        )}

                        {plumber.eta_minutes && (
                            <span className="rounded-full bg-cyan-50 px-2 py-0.5 text-xs font-medium text-cyan-700">
                                {plumber.eta_minutes} min away
                            </span>
                        )}
                    </div>
                </div>
            </div>

            <div className="mt-4 flex items-center gap-3">
                {plumber.is_online ? (
                    <span className="flex items-center gap-1.5 rounded-lg bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700">
                        <span className="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        Online
                    </span>
                ) : (
                    <span className="flex items-center gap-1.5 rounded-lg bg-slate-100 px-3 py-2 text-sm font-medium text-slate-500">
                        <span className="h-2 w-2 rounded-full bg-slate-400"></span>
                        Offline
                    </span>
                )}
            </div>
        </div>
    );
};

export default PlumberTrackingCard;