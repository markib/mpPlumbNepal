import React, { useState } from 'react';

import AiDiagnosisCard from './AiDiagnosisCard';
import AiScanningOverlay from './AiScanningOverlay';

import { startPipeline, getPipelineResult } from '../services/aiApi';
import { subscribeToPipeline } from '../services/pipelineSocket';

import type { AiDiagnosisResult } from '../types/ai';

interface Props {
    onAnalyze?: (message: string) => void;

    onResult: (
        result: AiDiagnosisResult | null
    ) => void;

    onReset?: () => void;
}

const AIRequestInput: React.FC<Props> = ({
    onAnalyze,
    onResult,
    onReset,
}) => {
    const [message, setMessage] =
        useState('');

    const [loading, setLoading] =
        useState(false);

    const [error, setError] =
        useState<string | null>(null);

    const [result, setResult] =
        useState<AiDiagnosisResult | null>(
            null
        );
        // const [pipelineId, setPipelineId] = useState<number | null>(null);
    const handleAnalyze = async () => {
        if (!message.trim()) {
            setError('Please describe your plumbing issue.');
            return;
        }

        try {
            setLoading(true);
            setError(null);
            setResult(null);

            onAnalyze?.(message);

            const res = await startPipeline(message);

            subscribeToPipeline(
                res.pipeline_id,
                {
                    onStepCompleted: (step, data) => {
                        console.log('📦 Step Completed:', step, data);

                        switch (step) {
                            case 'RunDiagnosisStep':
                                console.log('Diagnosis completed');
                                break;
                            case 'ValidateDiagnosisStep':
                                console.log('Validation completed');
                                break;
                            case 'GenerateRecommendationStep':
                                console.log('Recommendation completed');
                                break;
                            case 'StoreResultStep':
                                console.log('Result stored');
                                break;
                        }
                    },

                    // 💡 UPDATED: Expect the payload object containing your event properties
                    onCompleted: async (payload: { pipelineId: number; status: string }) => {
                        try {
                            const { pipelineId, status } = payload;

                            // 🛑 Intercept failures early before making unnecessary API requests
                            if (status !== 'completed') {
                                if (status === 'infrastructure_error') {
                                    setError('Local processing nodes are offline. Automatically falling back to cloud layers...');
                                } else if (status === 'rate_limited') {
                                    setError('The processing engine is currently busy. Retrying in a few moments...');
                                } else {
                                    setError('An error occurred during diagnostic analysis.');
                                }
                                setLoading(false);
                                return;
                            }

                            // 🚀 Success Path: Fetch your large result payload over clean HTTP
                            const response = await getPipelineResult(pipelineId);

                            setResult(response.result);
                            onResult(response.result);

                        } catch (error) {
                            console.error('Failed processing pipeline status signal:', error);
                            setError('Failed to load AI result.');
                        } finally {
                            setLoading(false);
                        }
                    },
                }
            );

        } catch (error) {
            console.error(error);
            const errorMessage =
                error instanceof Error
                    ? error.message
                    : 'Failed to analyze plumbing issue.';

            setError(errorMessage);
            setLoading(false);
            onResult(null);
        }
    };
    const handleReset = () => {
        setMessage('');

        setResult(null);

        setError(null);

        onResult(null);

        onReset?.();
    };
    
    
    return (
        <div className="space-y-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div>
                <h2 className="text-xl font-bold text-slate-900">
                    AI Plumbing Diagnosis
                </h2>

                <p className="mt-1 text-sm text-slate-500">
                    Describe your plumbing
                    issue to get an instant
                    AI diagnosis.
                </p>
            </div>

            <textarea
                rows={5}
                value={message}
                onChange={(e) =>
                    setMessage(
                        e.target.value
                    )
                }
                placeholder="Example: Water leaking under kitchen sink..."
                className="w-full rounded-xl border border-slate-300 p-4 focus:border-cyan-500 focus:outline-none"
            />

            <div className="flex gap-3">
                <button
                    type="button"
                    onClick={
                        handleAnalyze
                    }
                    disabled={loading}
                    className="flex-1 rounded-xl bg-cyan-600 py-3 font-semibold text-white transition hover:bg-cyan-700 disabled:bg-slate-300"
                >
                    {loading ? (
                        <span className="flex items-center justify-center gap-2">
                            <svg className="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                            </svg>
                            Scanning...
                        </span>
                    ) : (
                        'Analyze with AI'
                    )}
                </button>

                <button
                    type="button"
                    onClick={handleReset}
                    className="rounded-xl border border-slate-300 px-5 py-3 text-slate-700 transition hover:bg-slate-100"
                >
                    Reset
                </button>
            </div>

            {error && (
                <div className="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                    {error}
                </div>
            )}

            {loading && (
                <AiScanningOverlay
                    isScanning={loading}
                    message="Analyzing your plumbing issue..."
                />
            )}

            {result && !loading && (
                <AiDiagnosisCard
                    result={result}
                />
            )}
        </div>
    );
};

export default AIRequestInput;