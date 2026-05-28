import React, { useState } from 'react';
import BookingPage from './../features/booking/pages/BookingPage';
import SignUpPage from './SignUpPage';
import { apiUrl } from '../utils/api';
import type { AuthUser } from '../types';

interface HomePageProps {
  onLogin: (user: AuthUser, token: string) => void;
}

const HomePage: React.FC<HomePageProps> = ({ onLogin }) => {
  const [activeTab, setActiveTab] = useState<'login' | 'signup'>('login');
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);

  const handleLogin = async (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setError(null);
    setLoading(true);

    try {
      const response = await fetch(apiUrl('/api/v1/auth/login'), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
        },
        body: JSON.stringify({ email, password }),
      });

      if (!response.ok) {
        const data = await response.json().catch(() => null);
        setError(data?.message ?? 'Invalid credentials.');
        return;
      }

      const data = await response.json();
      onLogin(data.user, data.token);
    } catch (err) {
      setError('Unable to reach the server.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-slate-50 p-4">
      <div className="mx-auto max-w-7xl space-y-6">
        <header className="rounded-xl bg-white p-8 shadow-sm">
          <p className="mb-2 text-sm font-semibold uppercase tracking-wide text-cyan-600">PlumbNepal</p>
          <h1 className="text-3xl font-semibold text-slate-900 sm:text-4xl">
            Request plumbing service in minutes.
          </h1>
          <p className="mt-4 max-w-2xl text-slate-600">
            Book a plumber, shop keeper, or service provider from the home page. Create an account once and manage all requests in one place.
          </p>
        </header>

        <div className="grid gap-6 xl:grid-cols-[420px_minmax(0,1fr)]">
          <div className="space-y-6">
            <div className="rounded-xl bg-white p-6 shadow-sm">
              <div className="mb-4 flex items-center gap-2 rounded-xl bg-slate-100 p-2">
                <button
                  type="button"
                  onClick={() => setActiveTab('login')}
                  className={`flex-1 rounded-lg px-4 py-3 text-sm font-semibold ${
                    activeTab === 'login'
                      ? 'bg-white text-slate-900 shadow-sm'
                      : 'text-slate-600 hover:bg-white'
                  }`}
                >
                  Login
                </button>
                <button
                  type="button"
                  onClick={() => setActiveTab('signup')}
                  className={`flex-1 rounded-lg px-4 py-3 text-sm font-semibold ${
                    activeTab === 'signup'
                      ? 'bg-white text-slate-900 shadow-sm'
                      : 'text-slate-600 hover:bg-white'
                  }`}
                >
                  Sign up
                </button>
              </div>

              {activeTab === 'login' ? (
                <div>
                  <h2 className="mb-3 text-xl font-semibold text-slate-900">Login to your account</h2>
                  <p className="mb-4 text-sm text-slate-600">
                    Sign in as customer, admin, service provider, plumber, or shop keeper.
                  </p>
                  {error && (
                    <div className="mb-4 rounded-lg border border-rose-200 bg-rose-50 p-3 text-rose-900">
                      {error}
                    </div>
                  )}
                  <form onSubmit={handleLogin} className="space-y-4">
                    <label className="block">
                      <span className="text-sm font-medium text-slate-700">Email</span>
                      <input
                        type="email"
                        className="mt-1 w-full rounded border border-slate-300 bg-white px-3 py-2 outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-200"
                        value={email}
                        onChange={(e) => setEmail(e.target.value)}
                        required
                      />
                    </label>
                    <label className="block">
                      <span className="text-sm font-medium text-slate-700">Password</span>
                      <div className="relative mt-1">
                        <input
                          type={showPassword ? 'text' : 'password'}
                          className="w-full rounded border border-slate-300 bg-white px-3 py-2 pr-12 outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-200"
                          value={password}
                          onChange={(e) => setPassword(e.target.value)}
                          required
                        />
                        <button
                          type="button"
                          onClick={() => setShowPassword((prev) => !prev)}
                          className="absolute inset-y-0 right-0 flex items-center rounded-r px-3 text-sm text-slate-500 hover:text-slate-900"
                        >
                          {showPassword ? 'Hide' : 'Show'}
                        </button>
                      </div>
                    </label>
                    <button
                      type="submit"
                      className="w-full rounded bg-cyan-600 px-4 py-3 text-white transition hover:bg-cyan-700 disabled:cursor-not-allowed disabled:bg-slate-400"
                      disabled={loading}
                    >
                      {loading ? 'Signing in...' : 'Sign in'}
                    </button>
                  </form>
                </div>
              ) : (
                <SignUpPage onRegister={onLogin} />
              )}
            </div>

            <div className="rounded-xl bg-white p-6 shadow-sm">
              <h2 className="mb-3 text-xl font-semibold text-slate-900">Fast booking, public access</h2>
              <p className="text-sm text-slate-600">
                The request form is available on the home page for all users. No login is required to start a booking request, but accounts make tracking easier.
              </p>
            </div>
          </div>

          <div className="rounded-xl bg-white p-6 shadow-sm">
            <BookingPage />
          </div>
        </div>
      </div>
    </div>
  );
};

export default HomePage;
