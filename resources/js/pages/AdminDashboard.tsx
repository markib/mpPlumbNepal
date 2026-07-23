import React, { useEffect, useState } from 'react';
import { apiUrl } from '../utils/api';
import { authHeaders } from '../utils/auth';

interface AdminUser {
  id: number;
  name: string;
  email: string;
  phone: string;
  role: string;
  locale: string;
  verification_status?: string;
  citizenship_verified?: boolean;
  has_plumber_profile?: boolean;
  created_at: string;
}

interface VerificationDoc {
  id: number;
  user_id: number;
  document_type: string;
  file_path: string;
  status: string;
  review_notes?: string;
  created_at: string;
  user: { id: number; name: string; email: string; phone: string; role: string };
}

interface AdminBooking {
  id: number;
  workflow_status: string;
  payment_method: string;
  amount: number;
  is_emergency: boolean;
  landmark?: string;
  ward_number?: string;
  tole_name?: string;
  created_at: string;
  contracted_at?: string;
  service_type?: { id: number; name: string };
  customer?: { id: number; name: string; phone: string };
  plumber?: { id: number; name: string; phone: string } | null;
}

type Tab = 'users' | 'verifications' | 'bookings';

const AdminDashboard: React.FC = () => {
  const [tab, setTab] = useState<Tab>('users');

  const [users, setUsers] = useState<AdminUser[]>([]);
  const [usersTotal, setUsersTotal] = useState(0);
  const [usersPage, setUsersPage] = useState(1);
  const [roleFilter, setRoleFilter] = useState('');
  const [search, setSearch] = useState('');

  const [docs, setDocs] = useState<VerificationDoc[]>([]);
  const [docsTotal, setDocsTotal] = useState(0);
  const [docsPage, setDocsPage] = useState(1);
  const [docStatus, setDocStatus] = useState('pending');

  const [bookings, setBookings] = useState<AdminBooking[]>([]);
  const [bookingsTotal, setBookingsTotal] = useState(0);
  const [bookingsPage, setBookingsPage] = useState(1);
  const [statusFilter, setStatusFilter] = useState('');
  const [bookingSearch, setBookingSearch] = useState('');

  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);

  const fetchUsers = async (page = usersPage) => {
    setLoading(true);
    const params = new URLSearchParams();
    if (roleFilter) params.set('role', roleFilter);
    if (search) params.set('search', search);
    params.set('page', String(page));

    const res = await fetch(apiUrl(`/api/v1/admin/users?${params}`), { headers: authHeaders() });
    if (!res.ok) { setLoading(false); return; }
    const data = await res.json();
    setUsers(data.data || []);
    setUsersTotal(data.total ?? 0);
    setUsersPage(data.current_page ?? page);
    setLoading(false);
  };

  const fetchDocs = async (page = docsPage) => {
    setLoading(true);
    const params = new URLSearchParams({ status: docStatus, page: String(page) });
    const res = await fetch(apiUrl(`/api/v1/admin/verifications?${params}`), { headers: authHeaders() });
    if (!res.ok) { setLoading(false); return; }
    const data = await res.json();
    setDocs(data.data || []);
    setDocsTotal(data.total ?? 0);
    setDocsPage(data.current_page ?? page);
    setLoading(false);
  };

  const fetchBookings = async (page = bookingsPage) => {
    setLoading(true);
    const params = new URLSearchParams();
    if (statusFilter) params.set('workflow_status', statusFilter);
    if (bookingSearch) params.set('search', bookingSearch);
    params.set('page', String(page));

    const res = await fetch(apiUrl(`/api/v1/admin/bookings?${params}`), { headers: authHeaders() });
    if (!res.ok) { setLoading(false); return; }
    const data = await res.json();
    setBookings(data.data || []);
    setBookingsTotal(data.total ?? 0);
    setBookingsPage(data.current_page ?? page);
    setLoading(false);
  };

  useEffect(() => { fetchUsers(1); }, [roleFilter, search]);

  useEffect(() => { fetchDocs(1); }, [docStatus]);

  useEffect(() => { fetchBookings(1); }, [statusFilter, bookingSearch]);

  const handleApprove = async (id: number) => {
    setError(null);
    setSuccess(null);
    const res = await fetch(apiUrl(`/api/v1/admin/verifications/${id}/approve`), {
      method: 'POST', headers: authHeaders(), body: JSON.stringify({ review_notes: '' }),
    });
    if (!res.ok) { const d = await res.json().catch(() => null); setError(d?.message || 'Approve failed'); return; }
    setSuccess('Document approved');
    fetchDocs(docsPage);
  };

  const handleReject = async (id: number) => {
    const notes = prompt('Rejection reason:');
    if (!notes) return;
    setError(null);
    setSuccess(null);
    const res = await fetch(apiUrl(`/api/v1/admin/verifications/${id}/reject`), {
      method: 'POST', headers: authHeaders(), body: JSON.stringify({ review_notes: notes }),
    });
    if (!res.ok) { const d = await res.json().catch(() => null); setError(d?.message || 'Reject failed'); return; }
    setSuccess('Document rejected');
    fetchDocs(docsPage);
  };

  const counts = {
    users: usersTotal ?? 0,
    verifications: docsTotal ?? 0,
    bookings: bookingsTotal ?? 0,
  };

  const tabs: { key: Tab; label: string; icon: string }[] = [
    { key: 'users', label: 'Users', icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z' },
    { key: 'verifications', label: 'Verifications', icon: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z' },
    { key: 'bookings', label: 'Bookings', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2' },
  ];

  return (
    <div className="space-y-6">
      <div className="border-b border-slate-200">
        <nav className="-mb-px flex gap-6" role="tablist">
          {tabs.map(t => (
            <button
              key={t.key}
              type="button"
              role="tab"
              aria-selected={tab === t.key}
              onClick={() => setTab(t.key)}
              className={`group relative flex items-center gap-2 px-1 pb-3 pt-2 text-sm font-medium transition-colors ${
                tab === t.key ? 'text-cyan-600' : 'text-slate-500 hover:text-slate-700'
              }`}
            >
              <svg className={`h-4 w-4 ${tab === t.key ? 'text-cyan-600' : 'text-slate-400 group-hover:text-slate-600'}`} fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d={t.icon} />
              </svg>
              {t.label}
              {counts[t.key] > 0 && (
                <span className={`ml-1 rounded-full px-2 py-0.5 text-xs font-medium ${
                  tab === t.key ? 'bg-cyan-100 text-cyan-700' : 'bg-slate-100 text-slate-600'
                }`}>
                  {counts[t.key]}
                </span>
              )}
              <span className={`absolute bottom-0 left-0 right-0 h-0.5 rounded-full transition-colors ${
                tab === t.key ? 'bg-cyan-600' : 'bg-transparent'
              }`} />
            </button>
          ))}
        </nav>
      </div>

      {success && <div className="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-900">{success}</div>}
      {error && <div className="rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-900">{error}</div>}

      {tab === 'users' && (
        <div className="space-y-3">
          <div className="flex gap-2">
            <select
              value={roleFilter}
              onChange={e => setRoleFilter(e.target.value)}
              className="rounded-lg border border-slate-200 px-3 py-2 text-sm"
            >
              <option value="">All roles</option>
              <option value="customer">Customer</option>
              <option value="plumber">Plumber</option>
              <option value="admin">Admin</option>
              <option value="service_provider">Service Provider</option>
              <option value="shop_keeper">Shop Keeper</option>
            </select>
            <input
              type="text"
              placeholder="Search name, email, phone..."
              value={search}
              onChange={e => setSearch(e.target.value)}
              className="flex-1 rounded-lg border border-slate-200 px-3 py-2 text-sm"
            />
            <button type="button" onClick={() => fetchUsers(1)} className="rounded-lg bg-slate-800 px-4 py-2 text-sm text-white hover:bg-slate-900">Search</button>
          </div>
          <div className="overflow-x-auto rounded-xl border border-slate-200">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b bg-slate-50 text-left text-xs font-medium uppercase text-slate-500">
                  <th className="p-3">Name</th>
                  <th className="p-3">Email</th>
                  <th className="p-3">Phone</th>
                  <th className="p-3">Role</th>
                  <th className="p-3">Verified</th>
                  <th className="p-3">Created</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {users.map(u => (
                  <tr key={u.id} className="hover:bg-slate-50">
                    <td className="p-3 font-medium">{u.name}</td>
                    <td className="p-3 text-slate-600">{u.email}</td>
                    <td className="p-3 font-mono text-xs">{u.phone}</td>
                    <td className="p-3">
                      <span className="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium">{u.role.replace('_', ' ')}</span>
                    </td>
                    <td className="p-3">
                      <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${u.citizenship_verified ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'}`}>
                        {u.citizenship_verified ? 'Yes' : u.verification_status ?? 'No'}
                      </span>
                    </td>
                    <td className="p-3 text-xs text-slate-500">{new Date(u.created_at).toLocaleDateString()}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          <p className="text-xs text-slate-500">{usersTotal} user{usersTotal !== 1 ? 's' : ''}</p>
        </div>
      )}

      {tab === 'verifications' && (
        <div className="space-y-3">
          <div className="flex gap-2">
            <select
              value={docStatus}
              onChange={e => setDocStatus(e.target.value)}
              className="rounded-lg border border-slate-200 px-3 py-2 text-sm"
            >
              <option value="pending">Pending</option>
              <option value="approved">Approved</option>
              <option value="rejected">Rejected</option>
            </select>
          </div>
          {docs.length === 0 ? (
            <p className="text-sm text-slate-500">No {docStatus} verifications.</p>
          ) : (
            <div className="grid gap-3">
              {docs.map(doc => (
                <div key={doc.id} className="rounded-xl border border-slate-200 bg-slate-50 p-4">
                  <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                      <p className="font-medium">{doc.user.name}</p>
                      <p className="text-xs text-slate-500">{doc.user.email} &middot; {doc.user.phone}</p>
                      <div className="mt-1 flex gap-2 text-xs">
                        <span className="rounded-full bg-white px-2 py-0.5">{doc.document_type.replace('_', ' ')}</span>
                        <span className={`rounded-full px-2 py-0.5 ${doc.status === 'pending' ? 'bg-amber-100 text-amber-800' : doc.status === 'approved' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'}`}>
                          {doc.status}
                        </span>
                      </div>
                      {doc.review_notes && <p className="mt-1 text-xs text-slate-500">Notes: {doc.review_notes}</p>}
                    </div>
                    {doc.status === 'pending' && (
                      <div className="flex gap-2">
                        <button
                          type="button"
                          onClick={() => handleApprove(doc.id)}
                          disabled={loading}
                          className="rounded bg-emerald-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-emerald-700 disabled:bg-slate-400"
                        >
                          Approve
                        </button>
                        <button
                          type="button"
                          onClick={() => handleReject(doc.id)}
                          disabled={loading}
                          className="rounded bg-rose-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-rose-700 disabled:bg-slate-400"
                        >
                          Reject
                        </button>
                      </div>
                    )}
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      )}

      {tab === 'bookings' && (
        <div className="space-y-3">
          <div className="flex gap-2">
            <select
              value={statusFilter}
              onChange={e => setStatusFilter(e.target.value)}
              className="rounded-lg border border-slate-200 px-3 py-2 text-sm"
            >
              <option value="">All statuses</option>
              <option value="pending">Pending</option>
              <option value="proposed">Proposed</option>
              <option value="contracted">Contracted</option>
              <option value="in_progress">In Progress</option>
              <option value="completed">Completed</option>
            </select>
            <input
              type="text"
              placeholder="Search by customer name or booking ID..."
              value={bookingSearch}
              onChange={e => setBookingSearch(e.target.value)}
              className="flex-1 rounded-lg border border-slate-200 px-3 py-2 text-sm"
            />
            <button type="button" onClick={() => fetchBookings(1)} className="rounded-lg bg-slate-800 px-4 py-2 text-sm text-white hover:bg-slate-900">Search</button>
          </div>
          <div className="overflow-x-auto rounded-xl border border-slate-200">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b bg-slate-50 text-left text-xs font-medium uppercase text-slate-500">
                  <th className="p-3">ID</th>
                  <th className="p-3">Service</th>
                  <th className="p-3">Customer</th>
                  <th className="p-3">Plumber</th>
                  <th className="p-3">Amount</th>
                  <th className="p-3">Status</th>
                  <th className="p-3">Created</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {bookings.map(b => (
                  <tr key={b.id} className="hover:bg-slate-50">
                    <td className="p-3 font-mono text-xs">#{b.id}</td>
                    <td className="p-3 font-medium">{b.service_type?.name ?? 'N/A'}</td>
                    <td className="p-3">{b.customer?.name ?? 'N/A'}</td>
                    <td className="p-3 text-slate-600">{b.plumber?.name ?? '-'}</td>
                    <td className="p-3 font-semibold">Rs {b.amount ?? 0}</td>
                    <td className="p-3">
                      <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${
                        b.workflow_status === 'completed' ? 'bg-emerald-100 text-emerald-800' :
                        b.workflow_status === 'contracted' || b.workflow_status === 'in_progress' ? 'bg-blue-100 text-blue-800' :
                        b.workflow_status === 'proposed' ? 'bg-amber-100 text-amber-800' :
                        'bg-slate-100 text-slate-800'
                      }`}>
                        {b.workflow_status.replace('_', ' ')}
                      </span>
                    </td>
                    <td className="p-3 text-xs text-slate-500">{new Date(b.created_at).toLocaleDateString()}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          <p className="text-xs text-slate-500">{bookingsTotal} booking{bookingsTotal !== 1 ? 's' : ''}</p>
        </div>
      )}
    </div>
  );
};

export default AdminDashboard;
