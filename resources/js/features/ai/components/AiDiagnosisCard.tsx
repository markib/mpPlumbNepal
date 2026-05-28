import React from 'react';

import type { AiDiagnosisResult } from '../types/ai';

interface Props {
    result: AiDiagnosisResult | null;
}

const urgencyStyles: Record<string, string> = {
    high: 'from-red-500 to-red-600 text-white shadow-red-500/25',
    emergency: 'from-red-500 to-red-600 text-white shadow-red-500/25',
    medium: 'from-amber-500 to-orange-500 text-white shadow-amber-500/25',
    low: 'from-emerald-500 to-green-600 text-white shadow-emerald-500/25',
};

const AiDiagnosisCard: React.FC<Props> = ({ result }) => {
    if (!result) return null;

    const urgency = result.urgency?.toLowerCase() ?? 'unknown';
    const urgencyClass = urgencyStyles[urgency] ?? 'from-slate-500 to-slate-600 text-white shadow-slate-500/25';
    const confidence = result.confidence ?? 0;

    const confidenceColor = confidence >= 80 ? 'text-emerald-500' : confidence >= 50 ? 'text-amber-500' : 'text-red-500';

    return (
        <div className="overflow-hidden rounded-2xl bg-white shadow-xl ring-1 ring-slate-200/50">
            {/* Header with gradient */}
            <div className={`bg-gradient-to-r ${urgencyClass} px-6 py-5`}>
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-white/20 backdrop-blur-sm">
                            <svg className="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                            </svg>
                        </div>
                        <div>
                            <h2 className="text-xl font-bold text-white">AI Diagnosis</h2>
                            <p className="text-xs text-white/70">Analysis complete</p>
                        </div>
                    </div>
                    <span className="rounded-full bg-white/20 px-4 py-1.5 text-sm font-semibold backdrop-blur-sm">
                        {result.urgency ?? 'Unknown'}
                    </span>
                </div>
            </div>

            {/* Content */}
            <div className="p-6">
                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="group relative rounded-xl border border-slate-100 bg-slate-50/50 p-4 transition-all hover:border-cyan-200 hover:bg-cyan-50/30">
                        <div className="flex items-center gap-2">
                            <div className="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-100 text-blue-600">
                                <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <span className="text-xs font-medium uppercase tracking-wide text-slate-500">Issue Type</span>
                        </div>
                        <p className="mt-3 text-base font-semibold text-slate-900">{result.issue_type ?? 'Unknown'}</p>
                    </div>

                    <div className="group relative rounded-xl border border-slate-100 bg-slate-50/50 p-4 transition-all hover:border-cyan-200 hover:bg-cyan-50/30">
                        <div className="flex items-center gap-2">
                            <div className="flex h-7 w-7 items-center justify-center rounded-lg bg-violet-100 text-violet-600">
                                <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19.428 15.428a2 2 0 00-1.022-.547l-2.384-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                                </svg>
                            </div>
                            <span className="text-xs font-medium uppercase tracking-wide text-slate-500">Recommended Service</span>
                        </div>
                        <p className="mt-3 text-base font-semibold text-slate-900">{result.recommended_service ?? 'Not Available'}</p>
                    </div>

                    <div className="group relative rounded-xl border border-slate-100 bg-slate-50/50 p-4 transition-all hover:border-cyan-200 hover:bg-cyan-50/30">
                        <div className="flex items-center gap-2">
                            <div className="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600">
                                <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <span className="text-xs font-medium uppercase tracking-wide text-slate-500">Estimated Cost</span>
                        </div>
                        <p className="mt-3 text-base font-semibold text-slate-900">
                            Rs. {result.estimated_price_min ?? 0} - Rs. {result.estimated_price_max ?? 0}
                        </p>
                    </div>

                    <div className="group relative rounded-xl border border-slate-100 bg-slate-50/50 p-4 transition-all hover:border-cyan-200 hover:bg-cyan-50/30">
                        <div className="flex items-center gap-2">
                            <div className={`flex h-7 w-7 items-center justify-center rounded-lg ${confidence >= 80 ? 'bg-emerald-100 text-emerald-600' : confidence >= 50 ? 'bg-amber-100 text-amber-600' : 'bg-red-100 text-red-600'}`}>
                                <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </div>
                            <span className="text-xs font-medium uppercase tracking-wide text-slate-500">AI Confidence</span>
                        </div>
                        <div className="mt-3 flex items-center gap-2">
                            <span className="text-base font-semibold text-slate-900">{confidence}%</span>
                            <div className="h-2 flex-1 overflow-hidden rounded-full bg-slate-100">
                                <div
                                    className={`h-full rounded-full bg-gradient-to-r ${confidence >= 80 ? 'from-emerald-400 to-emerald-500' : confidence >= 50 ? 'from-amber-400 to-amber-500' : 'from-red-400 to-red-500'}`}
                                    style={{ width: `${confidence}%` }}
                                />
                            </div>
                        </div>
                    </div>
                </div>

                {/* Summary */}
                <div className="mt-6 overflow-hidden rounded-xl border border-slate-100 bg-slate-50/50">
                    <div className="border-b border-slate-100 bg-white/50 px-4 py-2.5">
                        <span className="text-xs font-medium uppercase tracking-wide text-slate-500">AI Summary</span>
                    </div>
                    <div className="p-4">
                        <p className="leading-relaxed text-slate-700">{result.summary ?? 'No summary generated by AI.'}</p>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default AiDiagnosisCard;