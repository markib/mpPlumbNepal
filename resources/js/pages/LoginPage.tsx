import React, { useState } from 'react';
import { apiUrl } from '../utils/api';
import type { AuthUser } from '../types';

interface LoginPageProps {
  onLogin: (user: AuthUser, token: string) => void;
}

const LoginPage: React.FC<LoginPageProps> = ({ onLogin }) => {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (event: React.FormEvent<HTMLFormElement>) => {
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
      <div className="mx-auto max-w-md rounded-xl bg-white p-8 shadow-lg">
        <h1 className="mb-4 text-2xl font-semibold text-slate-900">Login</h1>
        <p className="mb-6 text-sm text-slate-600">
          Sign in as client, admin, service provider, plumber, or shop keeper.
        </p>

        {error && (
          <div className="mb-4 rounded-lg border border-rose-200 bg-rose-50 p-3 text-rose-900">
            {error}
          </div>
        )}

        <form onSubmit={handleSubmit} className="space-y-4">
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
                className="absolute inset-y-0 right-0 flex items-center rounded-r px-3 text-slate-500 hover:text-slate-900"
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

        <div className="mt-6 rounded-lg bg-slate-100 p-4 text-sm text-slate-700">
          Use test accounts like <strong>customer@plumbnepal.test</strong>, <strong>admin@plumbnepal.test</strong>, <strong>service@plumbnepal.test</strong>, <strong>plumber@plumbnepal.test</strong>, or <strong>shop@plumbnepal.test</strong>.
        </div>
      </div>
    </div>
  );
};

export default LoginPage;
