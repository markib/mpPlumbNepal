import { NearbyPlumber } from '../types/booking';

interface Props {
    plumbers: NearbyPlumber[];
}

const NearbyPlumbersList = ({
    plumbers,
}: Props) => {
    if (!plumbers.length) return null;

    return (
        <div className="rounded-xl border border-cyan-100 bg-cyan-50 p-4">
            <div className="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 className="text-base font-semibold text-slate-900">
                        Nearby plumbers notified
                    </h3>
                    <p className="text-sm text-slate-600">
                        Quotes will appear in Deal Offers when plumbers respond.
                    </p>
                </div>
                <span className="rounded-full bg-white px-3 py-1 text-sm font-medium text-cyan-700">
                    {plumbers.length} found
                </span>
            </div>
            <div className="mt-4 grid gap-3 sm:grid-cols-2">
            {plumbers.map((plumber) => (
                <div
                    key={plumber.id}
                    className="rounded-lg border border-cyan-100 bg-white p-4"
                >
                    <p className="font-medium text-slate-900">
                        {plumber.user?.name ?? 'Available plumber'}
                    </p>
                    <div className="mt-2 flex flex-wrap gap-2 text-xs text-slate-600">
                        <span className="rounded-full bg-slate-100 px-2 py-1">
                            Rating {plumber.rating?.toFixed(1) ?? 'N/A'}
                        </span>
                        <span className="rounded-full bg-slate-100 px-2 py-1">
                            {plumber.distance_meters
                                ? `${(plumber.distance_meters / 1000).toFixed(1)} km`
                                : 'Distance pending'}
                        </span>
                        <span className="rounded-full bg-emerald-50 px-2 py-1 text-emerald-700">
                            Notified
                        </span>
                    </div>
                </div>
            ))}
            </div>
        </div>
    );
};

export default NearbyPlumbersList;
