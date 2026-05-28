import React, { useEffect, useState } from 'react';
import './i18n';
import '../css/app.css';
import 'leaflet/dist/leaflet.css';
import HomePage from './pages/HomePage';
import DashboardPage from './pages/DashboardPage';
import { apiUrl } from './utils/api';
import { clearAuthToken, fetchMe, getAuthToken, setAuthToken } from './utils/auth';
import type { AuthUser } from './types';

const AppComponent: React.FC = () => {
  const [user, setUser] = useState<AuthUser | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const loadUser = async () => {
      const token = getAuthToken();
      if (!token) {
        setLoading(false);
        return;
      }

      const currentUser = await fetchMe();
      setUser(currentUser);
      setLoading(false);
    };

    loadUser();
  }, []);

  const handleLogin = (loggedInUser: AuthUser, token: string) => {
    setAuthToken(token);
    setUser(loggedInUser);
  };

  const handleLogout = async () => {
    const token = getAuthToken();
    if (token) {
      await fetch(apiUrl('/api/v1/auth/logout'), {
        method: 'POST',
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: 'application/json',
          'Content-Type': 'application/json',
        },
      });
    }

    clearAuthToken();
    setUser(null);
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-slate-50 p-4">
        <div className="mx-auto max-w-md rounded-xl bg-white p-8 shadow-lg">Loading...</div>
      </div>
    );
  }

  return user ? <DashboardPage user={user} onLogout={handleLogout} /> : <HomePage onLogin={handleLogin} />;
};

export default AppComponent;
