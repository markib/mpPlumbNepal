import React from 'react';
import BookingPage from
  '../features/booking/pages/BookingPage';
import PlumberDashboard from './PlumberDashboard';
import CustomerProposalList from './CustomerProposalList';
import type { AuthUser } from '../types';

interface DashboardPageProps {
  user: AuthUser;
  onLogout: () => void;
}

const DashboardPage: React.FC<DashboardPageProps> = ({ user, onLogout }) => {
  const hasCoordinates =
    typeof user.location?.latitude === 'number' &&
    typeof user.location?.longitude === 'number';

  return (
    <div className="min-h-screen bg-slate-50 p-4">
      <div className="mx-auto max-w-6xl space-y-6">
        <header className="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/50">
          <div className="flex flex-col md:flex-row">
            <div className="flex items-center gap-5 bg-gradient-to-br from-cyan-500 to-cyan-600 p-6 text-white md:w-64 md:flex-shrink-0">
              <div className="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-white/20 text-2xl font-bold backdrop-blur-sm">
                {user.name?.charAt(0).toUpperCase() ?? 'U'}
              </div>
              <div className="min-w-0">
                <p className="text-xs font-medium text-white/70">Signed in as</p>
                <h1 className="truncate text-xl font-semibold">{user.name}</h1>
                <span className="inline-flex mt-1 rounded-full bg-white/20 px-2.5 py-0.5 text-xs font-medium capitalize">
                  {user.role.replace('_', ' ')}
                </span>
              </div>
            </div>

            <div className="flex-1 p-6">
              <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div className="group rounded-xl border border-slate-100 bg-slate-50/50 p-4 transition-all hover:border-cyan-200 hover:bg-cyan-50/30">
                  <div className="flex items-center gap-2.5">
                    <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-cyan-100 text-cyan-600">
                      <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                      </svg>
                    </div>
                    <span className="text-xs font-medium uppercase tracking-wide text-slate-500">Email</span>
                  </div>
                  <p className="mt-3 truncate text-sm font-semibold text-slate-800">{user.email}</p>
                </div>

                <div className="group rounded-xl border border-slate-100 bg-slate-50/50 p-4 transition-all hover:border-cyan-200 hover:bg-cyan-50/30">
                  <div className="flex items-center gap-2.5">
                    <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600">
                      <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                      </svg>
                    </div>
                    <span className="text-xs font-medium uppercase tracking-wide text-slate-500">Phone</span>
                  </div>
                  <p className="mt-3 truncate text-sm font-semibold text-slate-800">{user.phone}</p>
                </div>

                <div className="group rounded-xl border border-slate-100 bg-slate-50/50 p-4 transition-all hover:border-cyan-200 hover:bg-cyan-50/30">
                  <div className="flex items-center gap-2.5">
                    <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-violet-100 text-violet-600">
                      <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" />
                      </svg>
                    </div>
                    <span className="text-xs font-medium uppercase tracking-wide text-slate-500">Locale</span>
                  </div>
                  <p className="mt-3 text-sm font-semibold text-slate-800">{user.locale.toUpperCase()}</p>
                </div>

                {user.location ? (
                  <div className="group rounded-xl border border-slate-100 bg-slate-50/50 p-4 transition-all hover:border-cyan-200 hover:bg-cyan-50/30">
                    <div className="flex items-center gap-2.5">
                      <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-100 text-amber-600">
                        <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                      </div>
                      <span className="text-xs font-medium uppercase tracking-wide text-slate-500">Location</span>
                    </div>
                    <p className="mt-3 truncate text-sm font-semibold text-slate-800">
                      {user.location.address ??
                        user.location.description ??
                        (hasCoordinates
                          ? `${user.location.latitude.toFixed(4)}, ${user.location.longitude.toFixed(4)}`
                          : 'Location pending')}
                    </p>
                    {hasCoordinates ? (
                      <p className="mt-1 text-xs text-slate-500">
                        {user.location.latitude.toFixed(4)}, {user.location.longitude.toFixed(4)}
                      </p>
                    ) : null}
                  </div>
                ) : null}
              </div>
            </div>
          </div>

          <div className="border-t border-slate-100 bg-slate-50/50 px-6 py-3">
            <button
              type="button"
              onClick={onLogout}
              className="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-100 hover:text-slate-900"
            >
              <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
              </svg>
              Sign out
            </button>
          </div>
        </header>

        <section className="rounded-xl bg-white p-6 shadow-sm">
          <div className="mb-4 flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
            <div>
              <h2 className="text-xl font-semibold">Request Service</h2>
              <p className="text-sm text-slate-600">
                Use this form to create a new service booking from any role. Customers, plumbers, and partners can all request work from the dashboard.
              </p>
            </div>
            <span className="rounded-full bg-slate-100 px-3 py-1 text-sm text-slate-700">
              Role: {user.role.replace('_', ' ')}
            </span>
          </div>
          <BookingPage />
        </section>

        {user.role === 'customer' && (
          <section className="rounded-xl bg-white p-6 shadow-sm">
            <CustomerProposalList />
          </section>
        )}

        {user.role === 'plumber' && (
          <section className="rounded-xl bg-white p-6 shadow-sm">
            <h2 className="mb-4 text-xl font-semibold">Plumber Open Requests</h2>
            <PlumberDashboard />
          </section>
        )}

        {user.role === 'service_provider' && (
          <section className="rounded-xl bg-white p-6 shadow-sm">
            <h2 className="mb-4 text-xl font-semibold">Service Provider Dashboard</h2>
            <p className="text-slate-600">
              Manage service requests, monitor plumber availability, and review service performance.
            </p>
          </section>
        )}

        {user.role === 'shop_keeper' && (
          <section className="rounded-xl bg-white p-6 shadow-sm">
            <h2 className="mb-4 text-xl font-semibold">Shop Keeper Dashboard</h2>
            <p className="text-slate-600">
              View orders, track inventory, and support plumbers with required tools and parts.
            </p>
          </section>
        )}

        {user.role === 'admin' && (
          <section className="rounded-xl bg-white p-6 shadow-sm">
            <h2 className="mb-4 text-xl font-semibold">Admin Dashboard</h2>
            <p className="text-slate-600">
              Manage users, review plumber verifications, and oversee booking operations.
            </p>
          </section>
        )}
      </div>
    </div>
  );
};

export default DashboardPage;
