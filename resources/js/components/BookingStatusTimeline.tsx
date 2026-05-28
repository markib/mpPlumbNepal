import React, { useEffect, useState, useCallback } from 'react';
import { apiUrl } from '../utils/api';
import { getAuthToken } from '../utils/auth';

interface BookingStatusTimelineProps {
    bookingId: number;
    initialStatus: string;
    plumberName?: string;
    onStatusChange?: (status: string) => void;
}

interface BookingStatusData {
    workflow_status: string;
    status_id: number;
    updated_at: string;
    plumber?: {
        id: number;
        name: string;
        phone: string;
    } | null;
    contract_start_code?: string;
}

const statusSteps = [
    { key: 'pending', label: 'Pending', icon: 'clock' },
    { key: 'contracted', label: 'Assigned', icon: 'user-check' },
    { key: 'in_progress', label: 'In Progress', icon: 'wrench' },
    { key: 'completed', label: 'Completed', icon: 'check-circle' },
];

const getStepIndex = (workflowStatus: string): number => {
    const stepMap: Record<string, number> = {
        pending: 0,
        proposed: 0,
        contracted: 1,
        in_progress: 2,
        completed: 3,
        cancelled: -1,
    };
    return stepMap[workflowStatus] ?? 0;
};

export const BookingStatusTimeline: React.FC<BookingStatusTimelineProps> = ({
    bookingId,
    initialStatus,
    plumberName,
    onStatusChange,
}) => {
    const [workflowStatus, setWorkflowStatus] = useState(initialStatus);
    const [plumber, setPlumber] = useState<{ name: string; phone: string } | null>(null);
    const [lastUpdate, setLastUpdate] = useState<Date>(new Date());
    const [contractCode, setContractCode] = useState<string | null>(null);

    const fetchBookingStatus = useCallback(async () => {
        try {
            const token = getAuthToken();
            const response = await fetch(apiUrl(`/api/v1/bookings/${bookingId}`), {
                headers: {
                    Authorization: `Bearer ${token}`,
                    'Content-Type': 'application/json',
                },
            });

            if (!response.ok) {
                return;
            }

            const data = await response.json();

            if (data.workflow_status !== workflowStatus) {
                setWorkflowStatus(data.workflow_status);
                onStatusChange?.(data.workflow_status);
            }

            if (data.plumber) {
                setPlumber({
                    name: data.plumber.user?.name || plumberName || 'Assigned Plumber',
                    phone: data.plumber.user?.phone || '',
                });
            }

            if (data.contract_start_code) {
                setContractCode(data.contract_start_code);
            }

            setLastUpdate(new Date());
        } catch (error) {
            console.error('Failed to fetch booking status:', error);
        }
    }, [bookingId, workflowStatus, plumberName, onStatusChange]);

    useEffect(() => {
        fetchBookingStatus();

        const interval = setInterval(fetchBookingStatus, 10000);

        return () => clearInterval(interval);
    }, [fetchBookingStatus]);

    const currentStep = getStepIndex(workflowStatus);

    const renderIcon = (iconName: string, isActive: boolean, isCompleted: boolean) => {
        const iconClass = isActive || isCompleted
            ? 'w-6 h-6 text-white'
            : 'w-6 h-6 text-gray-400';

        switch (iconName) {
            case 'clock':
                return (
                    <svg className={iconClass} fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                );
            case 'user-check':
                return (
                    <svg className={iconClass} fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                );
            case 'wrench':
                return (
                    <svg className={iconClass} fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                );
            case 'check-circle':
                return (
                    <svg className={iconClass} fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                );
            default:
                return null;
        }
    };

    return (
        <div className="bg-white rounded-2xl shadow-lg p-6">
            {/* Header */}
            <div className="flex items-center justify-between mb-6">
                <h3 className="text-lg font-semibold text-gray-900">Booking Status</h3>
                <span className="text-sm text-gray-500">
                    Last updated: {lastUpdate.toLocaleTimeString()}
                </span>
            </div>

            {/* Timeline */}
            <div className="relative">
                {/* Progress Line */}
                <div className="absolute top-8 left-0 right-0 h-1 bg-gray-200 rounded-full">
                    <div
                        className="h-full bg-blue-500 rounded-full transition-all duration-500"
                        style={{ width: `${(currentStep / (statusSteps.length - 1)) * 100}%` }}
                    />
                </div>

                {/* Steps */}
                <div className="relative flex justify-between">
                    {statusSteps.map((step, index) => {
                        const isCompleted = index <= currentStep;
                        const isActive = index === currentStep;

                        return (
                            <div key={step.key} className="flex flex-col items-center">
                                <div
                                    className={`w-16 h-16 rounded-full flex items-center justify-center transition-all duration-300 ${
                                        isActive
                                            ? 'bg-blue-500 scale-110 shadow-lg shadow-blue-500/30'
                                            : isCompleted
                                            ? 'bg-green-500'
                                            : 'bg-gray-200'
                                    }`}
                                >
                                    {renderIcon(step.icon, isActive, isCompleted)}
                                </div>
                                <span
                                    className={`mt-3 text-sm font-medium ${
                                        isActive ? 'text-blue-600' : isCompleted ? 'text-gray-900' : 'text-gray-400'
                                    }`}
                                >
                                    {step.label}
                                </span>
                            </div>
                        );
                    })}
                </div>
            </div>

            {/* Plumber Info */}
            {plumber && currentStep >= 1 && (
                <div className="mt-6 p-4 bg-blue-50 rounded-xl">
                    <div className="flex items-center gap-3">
                        <div className="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center">
                            <svg className="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <div className="flex-1">
                            <p className="font-medium text-gray-900">{plumber.name}</p>
                            <p className="text-sm text-gray-500">{plumber.phone}</p>
                        </div>
                        {contractCode && (
                            <div className="text-right">
                                <p className="text-xs text-gray-500">Code</p>
                                <p className="font-mono font-bold text-lg text-blue-600">{contractCode}</p>
                            </div>
                        )}
                    </div>
                </div>
            )}

            {/* Status Message */}
            <div className="mt-4 text-center">
                {workflowStatus === 'pending' && (
                    <p className="text-gray-500">Waiting for plumber acceptance...</p>
                )}
                {workflowStatus === 'contracted' && (
                    <p className="text-blue-600">Plumber has been assigned. They will arrive soon.</p>
                )}
                {workflowStatus === 'in_progress' && (
                    <p className="text-amber-600">Service in progress...</p>
                )}
                {workflowStatus === 'completed' && (
                    <p className="text-green-600">Service completed successfully!</p>
                )}
                {workflowStatus === 'cancelled' && (
                    <p className="text-red-600">Booking was cancelled</p>
                )}
            </div>
        </div>
    );
};

export default BookingStatusTimeline;