import React, { useEffect, useState, useRef } from 'react';
import { apiUrl } from '../utils/api';
import { authHeaders } from '../utils/auth';
import { realtimeService } from '../services/realtime';
import { getAuthToken } from '../utils/auth';

interface OpenRequest {
  id: number;
  service_type_name: string;
  customer_name?: string;
  landmark?: string;
  ward_number?: string;
  tole_name?: string;
  created_at: string;
  latitude: number;
  longitude: number;
  distance_meters?: number;
}

interface AssignedJob {
  id: number;
  workflow_status: string;
  contract_start_code?: string;
  contract_terms?: {
    base_fee: number;
    material_cost: number;
    eta_minutes: number;
    details?: Record<string, unknown>;
  };
  job_order_json?: Record<string, unknown>;
  job_started_at?: string;
  service_type_name: string;
  landmark?: string;
  ward_number?: string;
  tole_name?: string;
  customer_name: string;
}

const PlumberDashboard: React.FC = () => {
  const [requests, setRequests] = useState<OpenRequest[]>([]);
  const [assignedJobs, setAssignedJobs] = useState<AssignedJob[]>([]);
  const [selectedRequest, setSelectedRequest] = useState<OpenRequest | null>(null);
  const [selectedJob, setSelectedJob] = useState<AssignedJob | null>(null);
  const [selectedCompleteJob, setSelectedCompleteJob] = useState<AssignedJob | null>(null);
  const [showModal, setShowModal] = useState(false);
  const [showOtpModal, setShowOtpModal] = useState(false);
  const [showCompleteModal, setShowCompleteModal] = useState(false);
  const [baseFee, setBaseFee] = useState('0');
  const [materialCost, setMaterialCost] = useState('0');
  const [etaMinutes, setEtaMinutes] = useState('30');
  const [notes, setNotes] = useState('');
  const [otpCode, setOtpCode] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);
  const [otpError, setOtpError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);
  const [otpLoading, setOtpLoading] = useState(false);
  const realtimeInitialized = useRef(false);
  const shownBookings = useRef(new Set<number>());

  useEffect(() => {
    const fetchRequests = async () => {
      try {
        const [openResponse, assignedResponse] = await Promise.all([
          fetch(apiUrl('/api/v1/plumber/open-requests'), { headers: authHeaders() }),
          fetch(apiUrl('/api/v1/plumber/assigned-jobs'), { headers: authHeaders() }),
        ]);

        if (!openResponse.ok) {
          throw new Error('Unable to load open requests');
        }

        if (!assignedResponse.ok) {
          throw new Error('Unable to load assigned jobs');
        }

        const openData = await openResponse.json();
        const assignedData = await assignedResponse.json();
        // setRequests(openData.requests || []);
        setRequests(prev => {
          const realtimeMap = new Map(
            prev.map(item => [item.id, item])
          );

          for (const request of openData.requests || []) {
            realtimeMap.set(request.id, request);
          }

          return Array.from(realtimeMap.values());
        });
        setAssignedJobs(assignedData.jobs || []);
      } catch (err) {
        console.error(err);
      }
    };

    // Show toast notification
    const showNotification = (message: string) => {
      console.log('SHOWING TOAST:', message);
      // Create notification element
      const toast = document.createElement('div');
      toast.className = 'fixed top-4 right-4 bg-green-600 text-white px-6 py-3 rounded-lg shadow-lg z-50 animate-pulse';
      toast.textContent = message;
      document.body.appendChild(toast);
      setTimeout(() => toast.remove(), 5000);
    };

    // Connect to realtime service
    const initRealtime = async () => {
      const token = getAuthToken();
      if (token) {
        try {
          // Fetch user data first
          const userResponse = await fetch(apiUrl('/api/v1/me'), { headers: authHeaders() });
          if (!userResponse.ok) throw new Error('Failed to fetch user');

          const userData = await userResponse.json();
          const plumberId = userData.plumber_profile_id;

          if (plumberId) {
            realtimeService.setPlumberId(plumberId);
            await realtimeService.connect(token);

            // Now listen for new booking broadcasts
            realtimeService.listenForBookings((booking) => {
              console.log('Dashboard booking callback fired');
              console.log('New booking received via realtime:', booking);

              // 1. Always normalize
              const normalized = {
                id: booking.id,
                service_type_name: booking.service_type_name,
                customer_name: booking.customer_name,
                landmark: booking.landmark,
                ward_number: booking.ward_number,
                tole_name: booking.tole_name,
                created_at: booking.created_at,
                latitude: booking.latitude,
                longitude: booking.longitude,
              };

              // ... update state logic ...
              // 2. Update OPEN requests only if not assigned
              setRequests(prev => {
                const existingIndex = prev.findIndex(
                  r => Number(r.id) === Number(normalized.id)
                );

                if (existingIndex >= 0) {
                  const copy = [...prev];
                  copy[existingIndex] = {
                    ...copy[existingIndex],
                    ...normalized,
                  };
                  return copy;
                }

                return [normalized, ...prev];
              });
            
              // showNotification(`New booking: ${serviceName}`);
              if (!shownBookings.current.has(booking.id)) {
                shownBookings.current.add(booking.id);

                showNotification(
                  `New booking: ${booking.service_type_name ??
                  'New Plumbing Request'
                  }`
                );
              }
              // fetchRequests();
            });

            realtimeService.listenForProposalAccepted(() => {
              fetchRequests();
              showNotification('Your quote was accepted. A new assigned job is ready.');
            });
          }
        } catch (err) {
          console.warn('Realtime setup failed, using polling only:', err);
        }
      }
    };

    fetchRequests();
    // initRealtime();
    if (!realtimeInitialized.current) {
      realtimeInitialized.current = true;
      initRealtime();
    }

    const refreshTimer = window.setInterval(fetchRequests, 15000);

    return () => {
      realtimeInitialized.current = false;
      shownBookings.current.clear();

      window.clearInterval(refreshTimer);

      realtimeService.disconnect();
    };
  }, []);

  const openQuoteModal = (request: OpenRequest) => {
    setSelectedRequest(request);
    setBaseFee('1200');
    setMaterialCost('300');
    setEtaMinutes('45');
    setNotes('Include plumbing repair and material estimate');
    setShowModal(true);
    setError(null);
    setSuccess(null);
  };

  const openOtpModal = (job: AssignedJob) => {
    setSelectedJob(job);
    setOtpCode('');
    setOtpError(null);
    setShowOtpModal(true);
  };

  const openCompleteModal = (job: AssignedJob) => {
    setSelectedCompleteJob(job);
    setError(null);
    setShowCompleteModal(true);
  };

  const submitQuote = async () => {
    if (!selectedRequest) return;
    setLoading(true);
    setError(null);

    try {
      const response = await fetch(apiUrl(`/api/v1/bookings/${selectedRequest.id}/proposals`), {
        method: 'POST',
        headers: {
          ...authHeaders(),
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          base_fee: Number(baseFee),
          material_cost: Number(materialCost),
          eta_minutes: Number(etaMinutes),
          proposal_terms: { notes },
        }),
      });

      if (!response.ok) {
        const data = await response.json().catch(() => null);
        setError(data?.message || 'Unable to send quote.');
        return;
      }

      setSuccess('Quote sent successfully. The customer has been notified.');
      setRequests((current) => current.filter((item) => item.id !== selectedRequest.id));
      setShowModal(false);
      setSelectedRequest(null);
    } catch (err) {
      setError('Unable to send quote.');
    } finally {
      setLoading(false);
    }
  };

  const submitStartJob = async () => {
    if (!selectedJob) return;
    setOtpLoading(true);
    setOtpError(null);

    try {
      const response = await fetch(apiUrl(`/api/v1/bookings/${selectedJob.id}/start-job`), {
        method: 'POST',
        headers: {
          ...authHeaders(),
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ contract_start_code: otpCode }),
      });

      if (!response.ok) {
        const data = await response.json().catch(() => null);
        setOtpError(data?.message || 'Unable to start job.');
        return;
      }

      const updated = await response.json();
      setAssignedJobs((current) => current.map((item) => (item.id === selectedJob.id ? { ...item, workflow_status: 'in_progress', job_started_at: updated.job_started_at } : item)));
      setShowOtpModal(false);
      setSelectedJob(null);
    } catch (err) {
      setOtpError('Unable to start job.');
    } finally {
      setOtpLoading(false);
    }
  };

  const completeJob = async (job: AssignedJob) => {
    setLoading(true);
    try {
      const response = await fetch(apiUrl(`/api/v1/bookings/${job.id}/complete-job`), {
        method: 'POST',
        headers: authHeaders(),
      });

      if (!response.ok) {
        const data = await response.json().catch(() => null);
        setError(data?.message || 'Unable to complete job.');
        return;
      }

      setAssignedJobs((current) => current.filter((item) => item.id !== job.id));
    } catch (err) {
      setError('Unable to complete job.');
    } finally {
      setLoading(false);
    }
  };

  const submitCompleteJob = async () => {
    if (!selectedCompleteJob) return;
    setLoading(true);
    setError(null);

    try {
      const response = await fetch(apiUrl(`/api/v1/bookings/${selectedCompleteJob.id}/complete-job`), {
        method: 'POST',
        headers: {
          ...authHeaders(),
          'Content-Type': 'application/json',
        },
      });

      if (!response.ok) {
        const data = await response.json().catch(() => null);
        setError(data?.message || 'Unable to complete job.');
        return;
      }

      setAssignedJobs((current) => current.filter((item) => item.id !== selectedCompleteJob.id));
      setShowCompleteModal(false);
      setSelectedCompleteJob(null);
    } catch (err) {
      setError('Unable to complete job.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="space-y-6">
      <div className="relative overflow-hidden rounded-xl bg-gradient-to-br from-cyan-500 via-cyan-600 to-cyan-700 p-6 shadow-lg">
        <div className="absolute right-0 top-0 h-32 w-32 translate-x-8 -translate-y-8 rounded-full bg-white/10"></div>
        <div className="absolute -bottom-4 -left-4 h-24 w-24 rounded-full bg-white/5"></div>
        <div className="relative flex items-start gap-4">
          <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white/20 backdrop-blur-sm">
            <svg className="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>
          <div>
            <h3 className="text-lg font-semibold text-white">Open Requests Near You</h3>
            <p className="mt-1 text-sm text-cyan-100">
              Nearby matching requests refresh automatically. Select one to accept the request and send your quote.
            </p>
          </div>
        </div>
        {requests.length > 0 && (
          <div className="relative mt-4 inline-flex items-center gap-2 rounded-full bg-white/20 px-3 py-1.5 text-sm font-medium text-white">
            <span className="relative flex h-2 w-2">
              <span className="absolute inline-flex h-full w-full animate-ping rounded-full bg-white opacity-75"></span>
              <span className="relative inline-flex h-2 w-2 rounded-full bg-white"></span>
            </span>
            {requests.length} new request{requests.length !== 1 ? 's' : ''} available
          </div>
        )}
      </div>

      {success && (
        <div className="relative overflow-hidden rounded-xl border border-emerald-200/50 bg-emerald-50 p-4 shadow-sm">
          <div className="absolute left-0 top-0 h-full w-1 bg-emerald-500"></div>
          <div className="flex items-start gap-3">
            <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-100">
              <svg className="h-5 w-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
              </svg>
            </div>
            <p className="text-sm font-medium text-emerald-900">{success}</p>
          </div>
        </div>
      )}

      {assignedJobs.length > 0 && (
        <div className="rounded-xl border border-slate-200 bg-white shadow-sm">
          <div className="border-b border-slate-100 bg-slate-50/50 px-6 py-4">
            <div className="flex items-center gap-3">
              <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-100">
                <svg className="h-5 w-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
              </div>
              <div>
                <h3 className="text-base font-semibold text-slate-900">Assigned Jobs</h3>
                <p className="text-sm text-slate-500">Contracted jobs assigned to you</p>
              </div>
              <span className="ml-auto rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700">
                {assignedJobs.length}
              </span>
            </div>
          </div>
          <div className="p-6">
            <div className="grid gap-4">
              {assignedJobs.map((job) => (
                <div key={job.id} className="group relative overflow-hidden rounded-xl border border-slate-200 bg-slate-50/50 p-4 transition-all hover:border-cyan-200 hover:shadow-md">
                  <div className="absolute left-0 top-0 h-full w-1 bg-gradient-to-b from-cyan-500 to-cyan-600 opacity-0 transition-opacity group-hover:opacity-100"></div>
                  <div className="flex flex-col gap-4 sm:flex-row sm:justify-between sm:items-start">
                    <div className="flex-1">
                      <div className="flex items-center gap-2">
                        <p className="text-sm font-semibold text-slate-900">{job.service_type_name}</p>
                        <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium capitalize ${
                          job.workflow_status === 'contracted'
                            ? 'bg-violet-100 text-violet-700'
                            : job.workflow_status === 'in_progress'
                            ? 'bg-cyan-100 text-cyan-700'
                            : 'bg-slate-100 text-slate-700'
                        }`}>
                          {job.workflow_status.replace('_', ' ')}
                        </span>
                      </div>
                      <p className="mt-2 flex items-center gap-1.5 text-sm text-slate-600">
                        <svg className="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        {job.landmark ?? 'No landmark'}, {job.tole_name ?? job.ward_number}
                      </p>
                      <p className="mt-1 flex items-center gap-1.5 text-sm text-slate-500">
                        <svg className="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        {job.customer_name}
                      </p>
                    </div>
                    {job.workflow_status === 'contracted' ? (
                      <button
                        type="button"
                        onClick={() => openOtpModal(job)}
                        className="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-violet-600 to-violet-700 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition-all hover:from-violet-700 hover:to-violet-800 hover:shadow-md"
                      >
                        <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        Enter Start Code
                      </button>
                    ) : (
                      <button
                        type="button"
                        onClick={() => openCompleteModal(job)}
                        className="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-emerald-600 to-emerald-700 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition-all hover:from-emerald-700 hover:to-emerald-800 hover:shadow-md"
                      >
                        <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                        </svg>
                        Complete Job
                      </button>
                    )}
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>
      )}

      {requests.length === 0 ? (
        <div className="flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-slate-200 bg-slate-50/50 p-12 text-center">
          <div className="flex h-16 w-16 items-center justify-center rounded-full bg-slate-100">
            <svg className="h-8 w-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <p className="mt-4 font-medium text-slate-600">No open requests found</p>
          <p className="mt-1 text-sm text-slate-500">Requests within your service radius will appear here</p>
        </div>
      ) : (
        <div className="grid gap-4">
          {requests.map((request) => (
            <div key={request.id} className="group relative overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm transition-all hover:border-cyan-200 hover:shadow-lg">
              <div className="absolute left-0 top-0 h-full w-1 bg-gradient-to-b from-cyan-400 to-cyan-600 opacity-0 transition-opacity group-hover:opacity-100"></div>
              <div className="p-5">
                <div className="flex items-start justify-between gap-4">
                  <div className="flex-1">
                    <div className="flex items-center gap-2">
                      <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-cyan-50">
                        <svg className="h-4 w-4 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                      </div>
                      <p className="text-base font-semibold text-slate-900">{request.service_type_name}</p>
                    </div>
                    <p className="mt-2.5 flex items-center gap-1.5 text-sm text-slate-600">
                      <svg className="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                      </svg>
                      {request.landmark ?? 'No landmark'}, {request.tole_name ?? request.ward_number}
                    </p>
                    <div className="mt-3 flex flex-wrap items-center gap-2">
                      <span className="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">
                        <svg className="h-3 w-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        {request.customer_name ?? 'Customer'}
                      </span>
                      <span className="inline-flex items-center gap-1 rounded-full bg-cyan-50 px-2.5 py-1 text-xs font-medium text-cyan-700">
                        <svg className="h-3 w-3 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        {request.distance_meters ? `${(request.distance_meters / 1000).toFixed(1)} km away` : 'Nearby'}
                      </span>
                    </div>
                    <p className="mt-2.5 text-xs text-slate-500">Requested {new Date(request.created_at).toLocaleString()}</p>
                  </div>
                  <button
                    type="button"
                    onClick={() => openQuoteModal(request)}
                    className="shrink-0 inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-cyan-600 to-cyan-700 px-4 py-2.5 text-sm font-medium text-white shadow-md transition-all hover:from-cyan-700 hover:to-cyan-800 hover:shadow-lg"
                  >
                    <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    Accept & Quote
                  </button>
                </div>
              </div>
            </div>
          ))}
        </div>
      )}

      {showModal && selectedRequest && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-sm">
          <div className="w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div className="relative border-b border-slate-100 bg-gradient-to-r from-cyan-500 to-cyan-600 p-6">
              <div className="absolute -right-4 -top-4 h-24 w-24 rounded-full bg-white/10"></div>
              <h3 className="text-xl font-semibold text-white">Accept Request and Send Quote</h3>
              <p className="mt-1 text-sm text-cyan-100">Review the request and send a quote with ETA and material cost.</p>
            </div>

            <div className="p-6">
              <div className="mb-6 rounded-xl bg-slate-50 p-4">
                <div className="flex items-center gap-3">
                  <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-cyan-100">
                    <svg className="h-5 w-5 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                  </div>
                  <div>
                    <p className="font-semibold text-slate-900">{selectedRequest.service_type_name}</p>
                    <p className="text-sm text-slate-600">{selectedRequest.landmark ?? 'No landmark'}, {selectedRequest.tole_name ?? selectedRequest.ward_number}</p>
                  </div>
                </div>
              </div>

              <div className="grid gap-4 md:grid-cols-3">
                <label className="block">
                  <span className="text-sm font-medium text-slate-700">Base Fee</span>
                  <div className="relative mt-1">
                    <span className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">Rs.</span>
                    <input
                      type="number"
                      value={baseFee}
                      onChange={(e) => setBaseFee(e.target.value)}
                      className="w-full rounded-lg border border-slate-300 pl-8 pr-3 py-2.5 focus:border-cyan-500 focus:ring-2 focus:ring-cyan-200"
                    />
                  </div>
                </label>
                <label className="block">
                  <span className="text-sm font-medium text-slate-700">Material Cost</span>
                  <div className="relative mt-1">
                    <span className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">Rs.</span>
                    <input
                      type="number"
                      value={materialCost}
                      onChange={(e) => setMaterialCost(e.target.value)}
                      className="w-full rounded-lg border border-slate-300 pl-8 pr-3 py-2.5 focus:border-cyan-500 focus:ring-2 focus:ring-cyan-200"
                    />
                  </div>
                </label>
                <label className="block">
                  <span className="text-sm font-medium text-slate-700">ETA (minutes)</span>
                  <div className="relative mt-1">
                    <input
                      type="number"
                      value={etaMinutes}
                      onChange={(e) => setEtaMinutes(e.target.value)}
                      className="w-full rounded-lg border border-slate-300 px-3 py-2.5 pr-10 focus:border-cyan-500 focus:ring-2 focus:ring-cyan-200"
                    />
                    <span className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm">min</span>
                  </div>
                </label>
              </div>

              <label className="block mt-4">
                <span className="text-sm font-medium text-slate-700">Proposal Notes</span>
                <textarea
                  value={notes}
                  onChange={(e) => setNotes(e.target.value)}
                  rows={4}
                  className="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-cyan-500 focus:ring-2 focus:ring-cyan-200"
                  placeholder="Add any additional details about the quote..."
                />
              </label>

              {error && (
                <div className="mt-4 flex items-center gap-2 rounded-lg border border-rose-200 bg-rose-50 p-3 text-rose-900">
                  <svg className="h-5 w-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <span className="text-sm">{error}</span>
                </div>
              )}

              <div className="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-end">
                <button
                  type="button"
                  onClick={() => setShowModal(false)}
                  className="rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50"
                >
                  Cancel
                </button>
                <button
                  type="button"
                  onClick={submitQuote}
                  disabled={loading}
                  className="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-cyan-600 to-cyan-700 px-5 py-2.5 text-sm font-medium text-white shadow-md transition-all hover:from-cyan-700 hover:to-cyan-800 disabled:cursor-not-allowed disabled:opacity-50"
                >
                  {loading ? (
                    <>
                      <svg className="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                      </svg>
                      Sending...
                    </>
                  ) : (
                    <>
                      <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                      </svg>
                      Send Quote
                    </>
                  )}
                </button>
              </div>
            </div>
          </div>
        </div>
      )}

      {showOtpModal && selectedJob && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-sm">
          <div className="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div className="relative border-b border-slate-100 bg-gradient-to-r from-violet-500 to-violet-600 p-6">
              <div className="absolute -right-4 -top-4 h-24 w-24 rounded-full bg-white/10"></div>
              <h3 className="text-xl font-semibold text-white">Enter Customer Start Code</h3>
              <p className="mt-1 text-sm text-violet-100">
                Ask the customer for the 4-digit code to verify arrival and start the job.
              </p>
            </div>

            <div className="p-6">
              <div className="mb-6 rounded-xl bg-slate-50 p-4">
                <div className="flex items-center gap-3">
                  <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-violet-100">
                    <svg className="h-5 w-5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                  </div>
                  <div>
                    <p className="font-semibold text-slate-900">{selectedJob.service_type_name}</p>
                    <p className="text-sm text-slate-600">{selectedJob.landmark ?? 'No landmark'}, {selectedJob.tole_name ?? selectedJob.ward_number}</p>
                  </div>
                </div>
              </div>

              <label className="block">
                <span className="text-sm font-medium text-slate-700">Start Code</span>
                <input
                  type="text"
                  value={otpCode}
                  onChange={(e) => setOtpCode(e.target.value)}
                  maxLength={4}
                  className="mt-2 w-full rounded-lg border-2 border-slate-200 px-4 py-3 text-center text-2xl font-bold tracking-widest text-slate-800 focus:border-violet-500 focus:ring-2 focus:ring-violet-200"
                  placeholder="----"
                />
              </label>

              {otpError && (
                <div className="mt-4 flex items-center gap-2 rounded-lg border border-rose-200 bg-rose-50 p-3 text-rose-900">
                  <svg className="h-5 w-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <span className="text-sm">{otpError}</span>
                </div>
              )}

              <div className="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-end">
                <button
                  type="button"
                  onClick={() => setShowOtpModal(false)}
                  className="rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50"
                >
                  Cancel
                </button>
                <button
                  type="button"
                  onClick={submitStartJob}
                  disabled={otpLoading || otpCode.length !== 4}
                  className="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-violet-600 to-violet-700 px-5 py-2.5 text-sm font-medium text-white shadow-md transition-all hover:from-violet-700 hover:to-violet-800 disabled:cursor-not-allowed disabled:opacity-50"
                >
                  {otpLoading ? (
                    <>
                      <svg className="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                      </svg>
                      Verifying...
                    </>
                  ) : (
                    <>
                      <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                      Start Job
                    </>
                  )}
                </button>
              </div>
            </div>
          </div>
        </div>
      )}

      {showCompleteModal && selectedCompleteJob && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-sm">
          <div className="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div className="relative border-b border-slate-100 bg-gradient-to-r from-emerald-500 to-emerald-600 p-6">
              <div className="absolute -right-4 -top-4 h-24 w-24 rounded-full bg-white/10"></div>
              <h3 className="text-xl font-semibold text-white">Complete Job</h3>
              <p className="mt-1 text-sm text-emerald-100">
                Confirm that the job is finished and the customer has approved the work.
              </p>
            </div>

            <div className="p-6">
              <div className="rounded-xl bg-slate-50 p-4">
                <div className="flex items-center gap-3">
                  <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-100">
                    <svg className="h-5 w-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                  </div>
                  <div>
                    <p className="font-semibold text-slate-900">{selectedCompleteJob.service_type_name}</p>
                    <p className="text-sm text-slate-600">{selectedCompleteJob.landmark ?? 'No landmark'}, {selectedCompleteJob.tole_name ?? selectedCompleteJob.ward_number}</p>
                  </div>
                </div>
                <div className="mt-3 flex items-center gap-2 border-t border-slate-200 pt-3">
                  <span className="text-xs font-medium text-slate-500">Current status:</span>
                  <span className="inline-flex items-center rounded-full bg-cyan-100 px-2.5 py-0.5 text-xs font-medium text-cyan-700 capitalize">
                    {selectedCompleteJob.workflow_status.replace('_', ' ')}
                  </span>
                </div>
              </div>

              {error && (
                <div className="mt-4 flex items-center gap-2 rounded-lg border border-rose-200 bg-rose-50 p-3 text-rose-900">
                  <svg className="h-5 w-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <span className="text-sm">{error}</span>
                </div>
              )}

              <div className="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-end">
                <button
                  type="button"
                  onClick={() => setShowCompleteModal(false)}
                  className="rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50"
                >
                  Cancel
                </button>
                <button
                  type="button"
                  onClick={submitCompleteJob}
                  disabled={loading}
                  className="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-emerald-600 to-emerald-700 px-5 py-2.5 text-sm font-medium text-white shadow-md transition-all hover:from-emerald-700 hover:to-emerald-800 disabled:cursor-not-allowed disabled:opacity-50"
                >
                  {loading ? (
                    <>
                      <svg className="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                      </svg>
                      Completing...
                    </>
                  ) : (
                    <>
                      <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                      </svg>
                      Confirm Complete
                    </>
                  )}
                </button>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default PlumberDashboard;
