import React from 'react';

import MapPinAddress from '../../../components/MapPinAddress';
import AIRequestInput from '../../ai/components/AIRequestInput';

import type { BookingFormValues, ServiceTypeOption } from '../types/booking';
import type { AiDiagnosisResult } from '../../ai/types/ai';

interface Props {
    booking: BookingFormValues;
    setBooking: React.Dispatch<React.SetStateAction<BookingFormValues>>;
    serviceTypes: ServiceTypeOption[];
    isLoadingTypes: boolean;
    isSubmitting: boolean;
    onAiComplete: (result: AiDiagnosisResult | null) => void;
    onSubmit: (e: React.FormEvent<HTMLFormElement>) => void;
}

const BookingForm: React.FC<Props> = ({
    booking,
    setBooking,
    serviceTypes,
    isLoadingTypes,
    isSubmitting,
    onAiComplete,
    onSubmit,
}) => {
    const paymentMethods = [
        { value: 'cod', label: 'Cash on Delivery', icon: '💵' },
        { value: 'esewa', label: 'eSewa', icon: '📱' },
        { value: 'khalti', label: 'Khalti', icon: '💳' },
        { value: 'ime_pay', label: 'IME Pay', icon: '🏦' },
    ];

    return (
        <section className="overflow-hidden rounded-2xl bg-white shadow-xl ring-1 ring-slate-200/50">
            {/* Header */}
            <div className="bg-gradient-to-r from-cyan-500 to-cyan-600 px-6 py-5">
                <div className="flex items-center gap-4">
                    <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-white/20 backdrop-blur-sm">
                        <svg className="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19.428 15.428a2 2 0 00-1.022-.547l-2.384-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                        </svg>
                    </div>
                    <div>
                        <h1 className="text-xl font-bold text-white">Request Plumbing Service</h1>
                        <p className="text-sm text-white/70">AI-powered instant diagnosis & dispatch</p>
                    </div>
                </div>
            </div>

            <form onSubmit={onSubmit} className="p-6 space-y-8">
                {/* Location Section */}
                <div className="space-y-4">
                    <div className="flex items-center gap-2">
                        <div className="flex h-6 w-6 items-center justify-center rounded-lg bg-cyan-100 text-cyan-600">
                            <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <h2 className="text-base font-semibold text-slate-900">Service Location</h2>
                    </div>
                    <div className="rounded-xl border border-slate-200 bg-slate-50/50 p-4">
                        <MapPinAddress value={booking} onChange={setBooking} />
                    </div>
                </div>

                {/* Service Details Section */}
                <div className="space-y-4">
                    <div className="flex items-center gap-2">
                        <div className="flex h-6 w-6 items-center justify-center rounded-lg bg-violet-100 text-violet-600">
                            <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </div>
                        <h2 className="text-base font-semibold text-slate-900">Service Details</h2>
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="sm:col-span-2">
                            <label className="block">
                                <span className="text-sm font-medium text-slate-700">Service Type</span>
                                {isLoadingTypes ? (
                                    <div className="mt-1.5 flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-500">
                                        <svg className="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                                            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                        </svg>
                                        Loading services...
                                    </div>
                                ) : (
                                    <div className="relative mt-1.5">
                                        <select
                                            value={booking.service_type_id}
                                            onChange={(e) => setBooking((prev) => ({ ...prev, service_type_id: Number(e.target.value) }))}
                                            className="w-full appearance-none rounded-xl border border-slate-300 bg-white px-4 py-3 pr-10 text-sm text-slate-900 transition-all focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-200"
                                        >
                                            {(serviceTypes ?? []).map((type) => (
                                                <option key={type.id} value={type.id}>{type.name}</option>
                                            ))}
                                        </select>
                                        <div className="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                            <svg className="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </div>
                                    </div>
                                )}
                            </label>
                        </div>

                        <label className="block">
                            <span className="text-sm font-medium text-slate-700">Landmark</span>
                            <div className="relative mt-1.5">
                                <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <svg className="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    </svg>
                                </div>
                                <input
                                    type="text"
                                    value={booking.landmark}
                                    onChange={(e) => setBooking((prev) => ({ ...prev, landmark: e.target.value }))}
                                    className="w-full rounded-xl border border-slate-300 pl-10 pr-4 py-3 text-sm transition-all focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-200"
                                    placeholder="Near temple, school, market..."
                                />
                            </div>
                        </label>

                        <label className="block">
                            <span className="text-sm font-medium text-slate-700">Ward Number</span>
                            <div className="relative mt-1.5">
                                <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <svg className="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                </div>
                                <input
                                    type="text"
                                    value={booking.ward_number}
                                    onChange={(e) => setBooking((prev) => ({ ...prev, ward_number: e.target.value }))}
                                    className="w-full rounded-xl border border-slate-300 pl-10 pr-4 py-3 text-sm transition-all focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-200"
                                    placeholder="e.g., Ward 5"
                                />
                            </div>
                        </label>

                        <label className="block">
                            <span className="text-sm font-medium text-slate-700">Tole Name</span>
                            <div className="relative mt-1.5">
                                <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <svg className="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </div>
                                <input
                                    type="text"
                                    value={booking.tole_name}
                                    onChange={(e) => setBooking((prev) => ({ ...prev, tole_name: e.target.value }))}
                                    className="w-full rounded-xl border border-slate-300 pl-10 pr-4 py-3 text-sm transition-all focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-200"
                                    placeholder="e.g., Baneshwor"
                                />
                            </div>
                        </label>

                        <label className="block">
                            <span className="text-sm font-medium text-slate-700">Payment Method</span>
                            <div className="relative mt-1.5">
                                <select
                                    value={booking.payment_method}
                                    onChange={(e) => setBooking((prev) => ({ ...prev, payment_method: e.target.value as BookingFormValues['payment_method'] }))}
                                    className="w-full appearance-none rounded-xl border border-slate-300 bg-white px-4 py-3 pr-10 text-sm text-slate-900 transition-all focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-200"
                                >
                                    {paymentMethods.map((method) => (
                                        <option key={method.value} value={method.value}>{method.icon} {method.label}</option>
                                    ))}
                                </select>
                                <div className="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                    <svg className="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>

                {/* Image Upload Section */}
                <div className="space-y-4">
                    <div className="flex items-center gap-2">
                        <div className="flex h-6 w-6 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600">
                            <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <h2 className="text-base font-semibold text-slate-900">Upload Image</h2>
                        <span className="text-xs text-slate-500">(Optional)</span>
                    </div>
                    <div className={`relative rounded-xl border-2 border-dashed ${booking.image ? 'border-cyan-300 bg-cyan-50/50' : 'border-slate-300 hover:border-cyan-400'} transition-colors`}>
                        {!booking.image ? (
                            <label className="flex cursor-pointer flex-col items-center justify-center p-8">
                                <div className="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100">
                                    <svg className="h-6 w-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
                                    </svg>
                                </div>
                                <p className="mt-3 text-sm font-medium text-slate-700">Click to upload image</p>
                                <p className="mt-1 text-xs text-slate-500">PNG, JPG up to 10MB</p>
                                <input
                                    type="file"
                                    accept="image/*"
                                    onChange={(e) => setBooking((prev) => ({ ...prev, image: e.target.files?.[0] || null }))}
                                    className="hidden"
                                />
                            </label>
                        ) : (
                            <div className="relative p-4">
                                <div className="relative overflow-hidden rounded-xl">
                                    <img src={URL.createObjectURL(booking.image)} alt="Preview" className="h-56 w-full object-cover" />
                                    <div className="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent" />
                                </div>
                                <div className="absolute bottom-4 left-4 right-4 flex items-center justify-between">
                                    <span className="truncate text-sm font-medium text-white">{booking.image.name}</span>
                                    <button
                                        type="button"
                                        onClick={() => setBooking((prev) => ({ ...prev, image: null }))}
                                        className="rounded-full bg-white/20 p-2 text-white backdrop-blur-sm transition hover:bg-white/30"
                                    >
                                        <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        )}
                    </div>
                </div>

                {/* AI Diagnosis Section */}
                <div className="space-y-4">
                    <div className="flex items-center gap-2">
                        <div className="flex h-6 w-6 items-center justify-center rounded-lg bg-amber-100 text-amber-600">
                            <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                            </svg>
                        </div>
                        <h2 className="text-base font-semibold text-slate-900">AI Diagnosis</h2>
                        <span className="text-xs text-slate-500">(Optional)</span>
                    </div>
                    <div className="rounded-xl border border-slate-200 bg-slate-50/50 p-4">
                        <AIRequestInput
                            onAnalyze={(message) => console.log('Analyzing:', message)}
                            onResult={onAiComplete}
                            onReset={() => {
                                setBooking((prev) => ({ ...prev, service_notes: '', ai_diagnosis_id: undefined }));
                                onAiComplete(null);
                            }}
                        />
                    </div>
                </div>

                {/* Emergency Toggle */}
                <label className={`flex items-start gap-4 rounded-xl border-2 p-4 transition-all ${booking.is_emergency ? 'border-red-300 bg-red-50' : 'border-slate-200 hover:border-slate-300'}`}>
                    <div className="relative mt-0.5">
                        <input
                            type="checkbox"
                            checked={booking.is_emergency}
                            onChange={(e) => setBooking((prev) => ({ ...prev, is_emergency: e.target.checked }))}
                            className="peer sr-only"
                        />
                        <div className={`h-6 w-11 rounded-full transition-colors ${booking.is_emergency ? 'bg-red-500' : 'bg-slate-200'}`}>
                            <div className={`mt-0.5 h-5 w-5 rounded-full bg-white shadow-sm transition-transform ${booking.is_emergency ? 'translate-x-5' : 'translate-x-0.5'}`} />
                        </div>
                    </div>
                    <div className="flex-1">
                        <p className={`font-semibold ${booking.is_emergency ? 'text-red-700' : 'text-slate-700'}`}>Emergency Service</p>
                        <p className="text-sm text-slate-500">Prioritize urgent plumber dispatch for critical issues</p>
                    </div>
                    {booking.is_emergency && (
                        <div className="flex h-8 w-8 items-center justify-center rounded-full bg-red-100 text-red-600">
                            <svg className="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fillRule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                            </svg>
                        </div>
                    )}
                </label>

                {/* Submit Button */}
                <button
                    type="submit"
                    disabled={isSubmitting || isLoadingTypes}
                    className="group relative flex w-full items-center justify-center gap-3 rounded-xl bg-gradient-to-r from-cyan-500 to-cyan-600 px-6 py-4 text-base font-bold text-white shadow-lg shadow-cyan-500/25 transition-all hover:from-cyan-600 hover:to-cyan-700 hover:shadow-xl hover:shadow-cyan-500/30 disabled:bg-slate-300 disabled:shadow-none"
                >
                    {isSubmitting ? (
                        <>
                            <svg className="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                            </svg>
                            <span>Processing Request...</span>
                        </>
                    ) : (
                        <>
                            <svg className="h-5 w-5 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            <span>Request Plumbing Service</span>
                        </>
                    )}
                </button>

                <p className="text-center text-xs text-slate-400">
                    By submitting, you agree to our terms of service
                </p>
            </form>
        </section>
    );
};

export default BookingForm;