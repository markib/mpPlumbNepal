import React, { useState } from 'react';
import { apiUrl } from '../utils/api';
import type { AuthUser, UserRole } from '../types';

interface SignUpPageProps {
  onRegister: (user: AuthUser, token: string) => void;
}

const roles: Array<{ value: UserRole; label: string }> = [
  { value: 'customer', label: 'Customer' },
  { value: 'service_provider', label: 'Service Provider' },
  { value: 'plumber', label: 'Plumber' },
  { value: 'shop_keeper', label: 'Shop Keeper' },
];

const SignUpPage: React.FC<SignUpPageProps> = ({ onRegister }) => {
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [phone, setPhone] = useState('');
  const [role, setRole] = useState<UserRole>('customer');
  const [locale, setLocale] = useState<'en' | 'ne'>('en');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const handleSubmit = async (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setError(null);
    setLoading(true);

    try {
      const response = await fetch(apiUrl('/api/v1/auth/register'), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
        },
        body: JSON.stringify({ name, email, password, phone, role, locale }),
      });

      if (!response.ok) {
        const data = await response.json().catch(() => null);
        setError(data?.message || 'Unable to register. Please check your inputs.');
        return;
      }

      const data = await response.json();
      onRegister(data.user, data.token);
    } catch (err) {
      setError('Unable to reach the server.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div>
      <h2 className="mb-3 text-xl font-semibold text-slate-900">Create a new account</h2>
      <p className="mb-4 text-sm text-slate-600">Sign up to save requests, manage bookings, and track order history.</p>

      {error && (
        <div className="mb-4 rounded-lg border border-rose-200 bg-rose-50 p-3 text-rose-900">
          {error}
        </div>
      )}

      <form onSubmit={handleSubmit} className="space-y-4">
        <label className="block">
          <span className="text-sm font-medium text-slate-700">Name</span>
          <input
            type="text"
            className="mt-1 w-full rounded border border-slate-300 bg-white px-3 py-2 outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-200"
            value={name}
            onChange={(e) => setName(e.target.value)}
            required
          />
        </label>

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
          <span className="text-sm font-medium text-slate-700">Phone</span>
          <input
            type="tel"
            className="mt-1 w-full rounded border border-slate-300 bg-white px-3 py-2 outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-200"
            value={phone}
            onChange={(e) => setPhone(e.target.value)}
            required
          />
        </label>

        <div className="grid gap-4 md:grid-cols-2">
          <label className="block">
            <span className="text-sm font-medium text-slate-700">Password</span>
            <input
              type="password"
              className="mt-1 w-full rounded border border-slate-300 bg-white px-3 py-2 outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-200"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              required
              minLength={8}
            />
          </label>

          <label className="block">
            <span className="text-sm font-medium text-slate-700">Role</span>
            <select
              className="mt-1 w-full rounded border border-slate-300 bg-white px-3 py-2 outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-200"
              value={role}
              onChange={(e) => setRole(e.target.value as UserRole)}
            >
              {roles.map((option) => (
                <option key={option.value} value={option.value}>
                  {option.label}
                </option>
              ))}
            </select>
          </label>
        </div>

        <label className="block">
          <span className="text-sm font-medium text-slate-700">Locale</span>
          <select
            className="mt-1 w-full rounded border border-slate-300 bg-white px-3 py-2 outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-200"
            value={locale}
            onChange={(e) => setLocale(e.target.value as 'en' | 'ne')}
          >
            <option value="en">English</option>
            <option value="ne">नेपाली</option>
          </select>
        </label>

        <button
          type="submit"
          className="w-full rounded bg-cyan-600 px-4 py-3 text-white transition hover:bg-cyan-700 disabled:cursor-not-allowed disabled:bg-slate-400"
          disabled={loading}
        >
          {loading ? 'Creating account...' : 'Sign up'}
        </button>
      </form>
    </div>
  );
};

export default SignUpPage;
