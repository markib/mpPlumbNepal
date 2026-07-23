import React, { useEffect, useState } from 'react';
import { apiUrl } from '../utils/api';
import { authHeaders } from '../utils/auth';

interface Proposal {
  id: number;
  base_fee: number;
  material_cost: number;
  total_cost: number;
  eta_minutes: number;
  distance_meters?: number | null;
  match_score?: number;
  skill_match?: boolean;
  proposal_terms?: Record<string, unknown>;
  created_at: string;
  booking: {
    id: number;
    service_type_id: number;
    service_type_name: string;
    landmark?: string;
    ward_number?: string;
    tole_name?: string;
  };
  plumber: {
    id: number;
    rating?: number;
    skills?: number[];
    is_online?: boolean;
    user: {
      name: string;
      phone: string;
    };
  };
}

interface JobOrder {
  id: number;
  service_type_name?: string;
  workflow_status: string;
  contract_terms?: Record<string, unknown>;
  job_order?: Record<string, unknown>;
  contract_start_code?: string;
  plumber: {
    name?: string;
    phone?: string;
  };
  location: {
    landmark?: string;
    ward_number?: string;
    tole_name?: string;
  };
  contracted_at?: string;
  job_started_at?: string;
}

interface PendingRequest {
  id: number;
  service_type_name?: string;
  workflow_status: string;
  broadcast_status?: string;
  broadcast_expires_at?: string;
  amount?: number;
  is_emergency?: boolean;
  landmark?: string;
  ward_number?: string;
  tole_name?: string;
  created_at: string;
}

const CustomerProposalList: React.FC = () => {
  const [proposals, setProposals] = useState<Proposal[]>([]);
  const [jobOrders, setJobOrders] = useState<JobOrder[]>([]);
  const [pendingRequests, setPendingRequests] = useState<PendingRequest[]>([]);
  const [loading, setLoading] = useState(false);
  const [success, setSuccess] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [creatingRequest, setCreatingRequest] = useState(false);
  const [selectedIds, setSelectedIds] = useState<Set<number>>(new Set());
  const [showCompare, setShowCompare] = useState(false);

  const refreshData = async () => {
    const [proposalResponse, jobOrderResponse, pendingResponse] = await Promise.all([
      fetch(apiUrl('/api/v1/customer/proposals'), {
        headers: authHeaders(),
      }),
      fetch(apiUrl('/api/v1/customer/job-orders'), {
        headers: authHeaders(),
      }),
      fetch(apiUrl('/api/v1/customer/pending-requests'), {
        headers: authHeaders(),
      }),
    ]);

    if (proposalResponse.ok) {
      const proposalData = await proposalResponse.json();
      setProposals(proposalData.proposals || []);
    }

    if (jobOrderResponse.ok) {
      const jobOrderData = await jobOrderResponse.json();
      setJobOrders(jobOrderData.job_orders || []);
    }

    if (pendingResponse.ok) {
      const pendingData = await pendingResponse.json();
      setPendingRequests(pendingData.pending_requests || []);
    }
  };

  useEffect(() => {
    refreshData();

    // Poll for updates every 15 seconds
    const pollInterval = setInterval(refreshData, 15000);

    return () => {
      clearInterval(pollInterval);
    };
  }, []);

  const acceptProposal = async (proposalId: number, bookingId: number) => {
    setLoading(true);
    setError(null);
    setSuccess(null);

    const response = await fetch(apiUrl(`/api/v1/bookings/${bookingId}/proposals/${proposalId}/accept`), {
      method: 'POST',
      headers: authHeaders(),
    });

    if (!response.ok) {
      const data = await response.json().catch(() => null);
      setError(data?.message || 'Unable to accept proposal.');
      setLoading(false);
      return;
    }

      const data = await response.json();
    setSuccess('Deal accepted. Start code: ' + data.job_order.contract_start_code);
    await refreshData();
    setLoading(false);
  };

  const createServiceRequest = async () => {
    setCreatingRequest(true);
    setError(null);
    setSuccess(null);

    try {
      // Create booking with default values
      const bookingData = {
        service_type_id: 1, // General Plumbing
        latitude: 27.7172,
        longitude: 85.3240,
        landmark: 'Test Location',
        ward_number: '1',
        tole_name: 'Test Tole',
        service_notes: 'Test service request',
        is_emergency: false,
        payment_method: 'cod',
      };

      const createResponse = await fetch(apiUrl('/api/v1/bookings'), {
        method: 'POST',
        headers: authHeaders(),
        body: JSON.stringify(bookingData),
      });

      if (!createResponse.ok) {
        const data = await createResponse.json().catch(() => null);
        setError(data?.message || 'Unable to create booking.');
        setCreatingRequest(false);
        return;
      }

      const createData = await createResponse.json();
      setSuccess('Service request created successfully. The plumber can now see it in open requests.');

      // Add the new booking to pending requests immediately without waiting for refresh
      if (createData.booking) {
        const newPendingRequest: PendingRequest = {
          id: createData.booking.id,
          service_type_name: createData.booking.service_type?.name,
          workflow_status: createData.booking.workflow_status || 'pending',
          broadcast_status: createData.broadcast_status,
          broadcast_expires_at: createData.broadcast_expires_at,
          amount: createData.booking.amount,
          is_emergency: createData.booking.is_emergency,
          landmark: createData.booking.landmark,
          ward_number: createData.booking.ward_number,
          tole_name: createData.booking.tole_name,
          created_at: createData.booking.created_at,
        };
        setPendingRequests(prev => [newPendingRequest, ...prev]);
      } else {
        await refreshData();
      }
    } catch (err) {
      setError('An error occurred while creating the service request.');
    } finally {
      setCreatingRequest(false);
    }
  };

  const printJobOrder = (order: JobOrder) => {
    const details = order.contract_terms || order.job_order || {};
    const html = `<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8" />
  <title>Job Order #${order.id}</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 32px; color: #111; }
    h1, h2, h3, p { margin: 0 0 12px; }
    .section { margin-bottom: 20px; }
    .box { border: 1px solid #ddd; padding: 16px; border-radius: 8px; }
    .label { font-weight: 700; color: #374151; }
    .value { margin-top: 4px; }
    pre { background: #f8fafc; padding: 12px; border-radius: 8px; }
  </style>
</head>
<body>
  <h1>PlumbNepal Job Order Receipt</h1>
  <p>Order ID: ${order.id}</p>
  <div class="section box">
    <h2>Plumber</h2>
    <p class="label">Name</p>
    <p class="value">${order.plumber.name || 'Unknown'}</p>
    <p class="label">Phone</p>
    <p class="value">${order.plumber.phone || 'Unknown'}</p>
  </div>
  <div class="section box">
    <h2>Job Details</h2>
    <p class="label">Service</p>
    <p class="value">${order.service_type_name || 'N/A'}</p>
    <p class="label">Status</p>
    <p class="value">${order.workflow_status}</p>
    <p class="label">Location</p>
    <p class="value">${order.location.landmark ?? 'N/A'}, ${order.location.tole_name ?? order.location.ward_number ?? 'N/A'}</p>
    <p class="label">Contracted on</p>
    <p class="value">${order.contracted_at ? new Date(order.contracted_at).toLocaleString() : 'N/A'}</p>
    ${order.job_started_at ? `<p class="label">Job started</p><p class="value">${new Date(order.job_started_at).toLocaleString()}</p>` : ''}
  </div>
  <div class="section box">
    <h2>Contract Terms</h2>
    <pre>${JSON.stringify(details, null, 2)}</pre>
  </div>
  <div class="section box">
    <h2>Start Code</h2>
    <p class="value">${order.contract_start_code || 'N/A'}</p>
  </div>
</body>
</html>`;

    const printWindow = window.open('', '_blank');
    if (!printWindow) {
      return;
    }

    printWindow.document.write(html);
    printWindow.document.close();
    printWindow.focus();
    printWindow.print();
  };

  const activeJobOrders = jobOrders.filter((order) => order.workflow_status !== 'completed');
  const completedJobOrders = jobOrders.filter((order) => order.workflow_status === 'completed');
  const hasContent = proposals.length > 0 || jobOrders.length > 0 || pendingRequests.length > 0;

  const toggleCompare = (id: number) => {
    setSelectedIds(prev => {
      const next = new Set(prev);
      if (next.has(id)) next.delete(id);
      else next.add(id);
      return next;
    });
  };

  const comparedProposals = proposals.filter(p => selectedIds.has(p.id));

  const refreshCustomerOffers = async () => {
    setLoading(true);
    setError(null);
    await refreshData();
    setLoading(false);
  };

  const noOffersCard = (
    <div className="rounded-xl bg-white p-6 shadow-sm">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h2 className="text-xl font-semibold">Deal Offers</h2>
          <p className="mt-2 text-sm text-slate-600">
            Your service request has been submitted. No active proposals or job orders have arrived yet.
          </p>
        </div>
        <div className="flex gap-2">
          <button
            type="button"
            onClick={createServiceRequest}
            disabled={creatingRequest}
            className="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-slate-400"
          >
            {creatingRequest ? 'Creating...' : 'Create Test Request'}
          </button>
          <button
            type="button"
            onClick={refreshCustomerOffers}
            disabled={loading}
            className="rounded bg-cyan-600 px-4 py-2 text-white hover:bg-cyan-700 disabled:cursor-not-allowed disabled:bg-slate-400"
          >
            {loading ? 'Refreshing...' : 'Refresh'}
          </button>
        </div>
      </div>
      <p className="mt-4 text-sm text-slate-500">
        Check back in a few minutes or refresh to see plumber proposals and job orders when they arrive.
      </p>
    </div>
  );

  return (
    <div className="space-y-4">
      <div className="rounded-xl bg-white p-6 shadow-sm">
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h2 className="text-xl font-semibold">Create Service Request</h2>
            <p className="mt-2 text-sm text-slate-600">
              Create a new service request and invite the test plumber (plumber@plumbnepal.test).
            </p>
          </div>
          <button
            type="button"
            onClick={createServiceRequest}
            disabled={creatingRequest}
            className="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-slate-400"
          >
            {creatingRequest ? 'Creating...' : 'Create Test Request'}
          </button>
        </div>
      </div>

      {success && (
        <div className="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-emerald-900">
          {success}
        </div>
      )}
      {error && (
        <div className="rounded-lg border border-rose-200 bg-rose-50 p-4 text-rose-900">
          {error}
        </div>
      )}

      {!hasContent ? noOffersCard : null}

      {pendingRequests.length > 0 && (
        <div className="rounded-xl bg-white p-6 shadow-sm">
          <h2 className="mb-4 text-xl font-semibold">Pending Service Requests</h2>
          <div className="space-y-4">
            {pendingRequests.map((request) => (
              <div key={request.id} className="rounded-lg border border-amber-200 bg-amber-50 p-4">
                <div className="flex items-center justify-between">
                  <div>
                    <h3 className="font-medium">{request.service_type_name || 'Service Request'}</h3>
                    <p className="text-sm text-slate-600">
                      {request.landmark || request.tole_name || request.ward_number || 'No location'}
                    </p>
                    <p className="mt-1 text-xs text-slate-500">
                      Submitted: {new Date(request.created_at).toLocaleString()}
                    </p>
                  </div>
                  <div className="text-right">
                    <span className={`inline-flex rounded px-2 py-1 text-xs font-medium ${
                      request.workflow_status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                      request.workflow_status === 'broadcasting' ? 'bg-blue-100 text-blue-800' :
                      request.workflow_status === 'expired' ? 'bg-red-100 text-red-800' :
                      'bg-gray-100 text-gray-800'
                    }`}>
                      {request.workflow_status}
                    </span>
                    {request.broadcast_status && (
                      <p className="mt-1 text-xs text-slate-500">
                        Broadcast: {request.broadcast_status}
                      </p>
                    )}
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {activeJobOrders.length > 0 && (
        <div className="rounded-xl bg-white p-6 shadow-sm">
          <div className="mb-4">
            <h2 className="text-xl font-semibold">Active Job Orders</h2>
            <p className="mt-2 text-sm text-slate-600">View details for your contracted jobs and download a receipt for each order.</p>
          </div>
          <div className="grid gap-4">
            {activeJobOrders.map((order) => (
              <div key={order.id} className="rounded-xl border border-slate-200 bg-slate-50 p-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                  <div>
                    <h3 className="text-lg font-semibold text-slate-900">{order.service_type_name || 'Service Order'}</h3>
                    <p className="mt-1 text-sm text-slate-600">Plumber: {order.plumber.name || 'N/A'}</p>
                    <p className="mt-1 text-sm text-slate-600">Status: {order.workflow_status.replace('_', ' ')}</p>
                    <p className="mt-1 text-sm text-slate-500">
                      Location: {order.location.landmark ?? 'N/A'}, {order.location.tole_name ?? order.location.ward_number ?? 'N/A'}
                    </p>
                  </div>
                  <button
                    type="button"
                    onClick={() => printJobOrder(order)}
                    className="rounded bg-slate-800 px-4 py-2 text-white hover:bg-slate-900"
                  >
                    Download Receipt
                  </button>
                </div>
                <div className="mt-4 grid gap-4 sm:grid-cols-2">
                  <div className="rounded-xl bg-white p-4">
                    <p className="text-sm text-slate-500">Contract start code</p>
                    <p className="mt-2 text-lg font-semibold">{order.contract_start_code || 'N/A'}</p>
                  </div>
                  <div className="rounded-xl bg-white p-4">
                    <p className="text-sm text-slate-500">Contracted at</p>
                    <p className="mt-2 text-lg font-semibold">{order.contracted_at ? new Date(order.contracted_at).toLocaleString() : 'Pending'}</p>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {completedJobOrders.length > 0 && (
        <div className="rounded-xl bg-white p-6 shadow-sm">
          <div className="mb-4">
            <h2 className="text-xl font-semibold">Completed Job History</h2>
            <p className="mt-2 text-sm text-slate-600">Review jobs that have been completed and keep receipts for your records.</p>
          </div>
          <div className="grid gap-4">
            {completedJobOrders.map((order) => (
              <div key={order.id} className="rounded-xl border border-slate-200 bg-slate-50 p-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                  <div>
                    <h3 className="text-lg font-semibold text-slate-900">{order.service_type_name || 'Service Order'}</h3>
                    <p className="mt-1 text-sm text-slate-600">Plumber: {order.plumber.name || 'N/A'}</p>
                    <p className="mt-1 text-sm text-slate-600">Status: Completed</p>
                    <p className="mt-1 text-sm text-slate-500">
                      Location: {order.location.landmark ?? 'N/A'}, {order.location.tole_name ?? order.location.ward_number ?? 'N/A'}
                    </p>
                  </div>
                  <button
                    type="button"
                    onClick={() => printJobOrder(order)}
                    className="rounded bg-slate-800 px-4 py-2 text-white hover:bg-slate-900"
                  >
                    Download Receipt
                  </button>
                </div>
                <div className="mt-4 grid gap-4 sm:grid-cols-2">
                  <div className="rounded-xl bg-white p-4">
                    <p className="text-sm text-slate-500">Started on</p>
                    <p className="mt-2 text-lg font-semibold">{order.job_started_at ? new Date(order.job_started_at).toLocaleString() : 'N/A'}</p>
                  </div>
                  <div className="rounded-xl bg-white p-4">
                    <p className="text-sm text-slate-500">Contracted at</p>
                    <p className="mt-2 text-lg font-semibold">{order.contracted_at ? new Date(order.contracted_at).toLocaleString() : 'N/A'}</p>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      <div className="rounded-xl bg-white p-6 shadow-sm">
        <div className="flex items-center justify-between">
          <div>
            <h2 className="text-xl font-semibold">Deal Offers</h2>
            <p className="mt-2 text-sm text-slate-600">Review ranked plumber quotes by rating, distance, ETA, price, and skill match.</p>
          </div>
          {proposals.length > 1 && (
            <div className="flex gap-2">
              {selectedIds.size >= 2 && !showCompare && (
                <button
                  type="button"
                  onClick={() => setShowCompare(true)}
                  className="rounded bg-cyan-600 px-4 py-2 text-sm font-medium text-white hover:bg-cyan-700"
                >
                  Compare {selectedIds.size} proposals
                </button>
              )}
              {showCompare && (
                <button
                  type="button"
                  onClick={() => { setShowCompare(false); setSelectedIds(new Set()); }}
                  className="rounded bg-slate-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-300"
                >
                  Exit comparison
                </button>
              )}
            </div>
          )}
        </div>

        {showCompare && comparedProposals.length >= 2 && (
          <div className="mt-6 overflow-x-auto rounded-xl border border-slate-200">
            <table className="w-full min-w-[600px] text-sm">
              <thead>
                <tr className="border-b border-slate-200 bg-slate-50">
                  <th className="p-3 text-left font-medium text-slate-500">Metric</th>
                  {comparedProposals.map(p => (
                    <th key={p.id} className="p-3 text-left font-semibold text-slate-900">
                      {p.plumber.user.name}
                    </th>
                  ))}
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                <tr className="hover:bg-slate-50/50">
                  <td className="p-3 font-medium text-slate-500">Rating</td>
                  {comparedProposals.map(p => (
                    <td key={p.id} className="p-3">
                      <span className="inline-flex items-center gap-1">
                        {p.plumber.rating?.toFixed(1) ?? 'N/A'}
                        <span className="text-amber-400">★</span>
                      </span>
                    </td>
                  ))}
                </tr>
                <tr className="hover:bg-slate-50/50">
                  <td className="p-3 font-medium text-slate-500">Base Fee</td>
                  {comparedProposals.map(p => (
                    <td key={p.id} className={`p-3 font-semibold ${p.base_fee === Math.min(...comparedProposals.map(x => x.base_fee)) ? 'text-emerald-700' : ''}`}>
                      Rs {p.base_fee}
                    </td>
                  ))}
                </tr>
                <tr className="hover:bg-slate-50/50">
                  <td className="p-3 font-medium text-slate-500">Material Cost</td>
                  {comparedProposals.map(p => (
                    <td key={p.id} className={`p-3 font-semibold ${p.material_cost === Math.min(...comparedProposals.map(x => x.material_cost)) ? 'text-emerald-700' : ''}`}>
                      Rs {p.material_cost}
                    </td>
                  ))}
                </tr>
                <tr className="hover:bg-slate-50/50">
                  <td className="p-3 font-medium text-slate-500">Total</td>
                  {comparedProposals.map(p => (
                    <td key={p.id} className={`p-3 text-lg font-bold ${p.total_cost === Math.min(...comparedProposals.map(x => x.total_cost)) ? 'text-emerald-700' : 'text-slate-900'}`}>
                      Rs {p.total_cost}
                    </td>
                  ))}
                </tr>
                <tr className="hover:bg-slate-50/50">
                  <td className="p-3 font-medium text-slate-500">ETA</td>
                  {comparedProposals.map(p => (
                    <td key={p.id} className={`p-3 font-semibold ${p.eta_minutes === Math.min(...comparedProposals.map(x => x.eta_minutes)) ? 'text-emerald-700' : ''}`}>
                      {p.eta_minutes} min
                    </td>
                  ))}
                </tr>
                <tr className="hover:bg-slate-50/50">
                  <td className="p-3 font-medium text-slate-500">Distance</td>
                  {comparedProposals.map(p => (
                    <td key={p.id} className="p-3">
                      {p.distance_meters ? `${(p.distance_meters / 1000).toFixed(1)} km` : 'N/A'}
                    </td>
                  ))}
                </tr>
                <tr className="hover:bg-slate-50/50">
                  <td className="p-3 font-medium text-slate-500">Match</td>
                  {comparedProposals.map(p => (
                    <td key={p.id} className="p-3">
                      {p.match_score !== undefined ? (
                        <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-semibold ${p.match_score === Math.max(...comparedProposals.map(x => x.match_score ?? 0)) ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600'}`}>
                          {p.match_score}%
                        </span>
                      ) : 'N/A'}
                    </td>
                  ))}
                </tr>
                <tr className="hover:bg-slate-50/50">
                  <td className="p-3 font-medium text-slate-500">Skill match</td>
                  {comparedProposals.map(p => (
                    <td key={p.id} className="p-3">
                      <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-semibold ${p.skill_match ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'}`}>
                        {p.skill_match ? 'Matched' : 'Unconfirmed'}
                      </span>
                    </td>
                  ))}
                </tr>
                <tr className="hover:bg-slate-50/50">
                  <td className="p-3 font-medium text-slate-500">Phone</td>
                  {comparedProposals.map(p => (
                    <td key={p.id} className="p-3 font-mono text-xs">{p.plumber.user.phone}</td>
                  ))}
                </tr>
                <tr>
                  <td className="p-3 font-medium text-slate-500">Action</td>
                  {comparedProposals.map(p => (
                    <td key={p.id} className="p-3">
                      <button
                        type="button"
                        onClick={() => acceptProposal(p.id, p.booking.id)}
                        disabled={loading}
                        className="rounded bg-cyan-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-cyan-700 disabled:cursor-not-allowed disabled:bg-slate-400"
                      >
                        Accept
                      </button>
                    </td>
                  ))}
                </tr>
              </tbody>
            </table>
          </div>
        )}

        {proposals.length === 0 ? (
          <p className="mt-4 text-sm text-slate-600">No active proposals at the moment.</p>
        ) : (
          <div className="mt-6 grid gap-4">
            {proposals.map((proposal) => (
              <div key={proposal.id} className={`rounded-xl p-6 shadow-sm ${showCompare && selectedIds.has(proposal.id) ? 'ring-2 ring-cyan-500 bg-cyan-50' : 'bg-slate-50'}`}>
                <div className="flex flex-col gap-3 sm:flex-row sm:justify-between sm:items-start">
                  <div className="flex items-start gap-3">
                    {proposals.length > 1 && (
                      <input
                        type="checkbox"
                        checked={selectedIds.has(proposal.id)}
                        onChange={() => toggleCompare(proposal.id)}
                        className="mt-1 h-4 w-4 rounded border-slate-300 text-cyan-600 focus:ring-cyan-500"
                      />
                    )}
                    <div>
                      <div className="flex flex-wrap items-center gap-2">
                        <h3 className="text-lg font-semibold text-slate-900">{proposal.booking.service_type_name}</h3>
                        {proposal.match_score !== undefined && (
                          <span className="rounded-full bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-800">
                            Match {proposal.match_score}%
                          </span>
                        )}
                      </div>
                      <p className="mt-1 text-sm text-slate-600">Proposal from {proposal.plumber.user.name}</p>
                      <p className="mt-2 text-sm text-slate-500">
                        Location: {proposal.booking.landmark ?? 'N/A'}, {proposal.booking.tole_name ?? proposal.booking.ward_number}
                      </p>
                      <div className="mt-3 flex flex-wrap gap-2 text-xs text-slate-600">
                        <span className="rounded-full bg-white px-2 py-1">
                          Rating {proposal.plumber.rating?.toFixed(1) ?? 'N/A'}
                        </span>
                        <span className="rounded-full bg-white px-2 py-1">
                          {proposal.distance_meters ? `${(proposal.distance_meters / 1000).toFixed(1)} km away` : 'Distance pending'}
                        </span>
                        <span className={`rounded-full px-2 py-1 ${proposal.skill_match ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'}`}>
                          {proposal.skill_match ? 'Skill matched' : 'Skill not confirmed'}
                        </span>
                      </div>
                    </div>
                  </div>
                  <button
                    type="button"
                    onClick={() => acceptProposal(proposal.id, proposal.booking.id)}
                    disabled={loading}
                    className="rounded bg-cyan-600 px-4 py-2 text-white hover:bg-cyan-700 disabled:cursor-not-allowed disabled:bg-slate-400"
                  >
                    Accept Deal
                  </button>
                </div>
                <div className="mt-4 grid gap-2 sm:grid-cols-3">
                  <div>
                    <p className="text-sm text-slate-500">Base Fee</p>
                    <p className="text-lg font-semibold">Rs {proposal.base_fee}</p>
                  </div>
                  <div>
                    <p className="text-sm text-slate-500">Material Cost</p>
                    <p className="text-lg font-semibold">Rs {proposal.material_cost}</p>
                  </div>
                  <div>
                    <p className="text-sm text-slate-500">ETA</p>
                    <p className="text-lg font-semibold">{proposal.eta_minutes} min</p>
                  </div>
                  <div>
                    <p className="text-sm text-slate-500">Total Quote</p>
                    <p className="text-lg font-semibold">Rs {proposal.total_cost}</p>
                  </div>
                </div>
                {proposal.proposal_terms && (
                  <div className="mt-4 rounded-xl bg-white p-4 text-sm text-slate-700">
                    <p className="font-semibold">Proposal details</p>
                    <pre className="mt-2 whitespace-pre-wrap text-sm">{JSON.stringify(proposal.proposal_terms, null, 2)}</pre>
                  </div>
                )}
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
};

export default CustomerProposalList;
