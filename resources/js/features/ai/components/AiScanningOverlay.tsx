import React from 'react';

interface Props {
    isScanning: boolean;
    message?: string;
}

const AiScanningOverlay: React.FC<Props> = ({
    isScanning,
    message = 'Analyzing your plumbing issue...',
}) => {
    if (!isScanning) return null;

    return (
        <div className="relative overflow-hidden rounded-2xl border border-cyan-200/50 bg-gradient-to-br from-cyan-50 via-white to-cyan-50 p-8 shadow-lg">
            {/* Scanning grid background */}
            <div className="absolute inset-0 opacity-30">
                <div className="absolute inset-0" style={{
                    backgroundImage: `
                        linear-gradient(90deg, cyan 1px, transparent 1px),
                        linear-gradient(180deg, cyan 1px, transparent 1px)
                    `,
                    backgroundSize: '40px 40px',
                }} />
            </div>

            {/* Animated scan line */}
            <div className="absolute inset-0 overflow-hidden">
                <div className="absolute left-0 right-0 h-1 bg-gradient-to-r from-transparent via-cyan-400 to-transparent opacity-70">
                    <div className="animate-scan-line absolute inset-0 bg-gradient-to-r from-transparent via-cyan-300 to-transparent" />
                </div>
            </div>

            {/* Pulse rings */}
            <div className="relative flex flex-col items-center justify-center py-12">
                <div className="relative">
                    <div className="flex h-24 w-24 items-center justify-center">
                        {/* Multiple pulse rings */}
                        <div className="absolute h-24 w-24 animate-ping rounded-full border-2 border-cyan-400 opacity-20" />
                        <div className="absolute h-24 w-24 animate-ping rounded-full border-2 border-cyan-300 opacity-30" style={{ animationDelay: '0.2s' }} />
                        <div className="absolute h-24 w-24 animate-ping rounded-full border-2 border-cyan-200 opacity-40" style={{ animationDelay: '0.4s' }} />
                    </div>

                    {/* Center icon */}
                    <div className="relative flex h-24 w-24 items-center justify-center rounded-full bg-gradient-to-br from-cyan-500 to-cyan-600 shadow-lg shadow-cyan-500/40">
                        <svg className="h-10 w-10 text-white animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                </div>

                <div className="mt-8 text-center">
                    <div className="flex items-center justify-center gap-2">
                        <span className="h-2 w-2 rounded-full bg-cyan-500 animate-bounce" />
                        <span className="h-2 w-2 rounded-full bg-cyan-500 animate-bounce" style={{ animationDelay: '0.1s' }} />
                        <span className="h-2 w-2 rounded-full bg-cyan-500 animate-bounce" style={{ animationDelay: '0.2s' }} />
                    </div>
                    <h3 className="mt-4 text-lg font-semibold text-slate-800">
                        AI is analyzing
                    </h3>
                    <p className="mt-2 text-sm text-slate-500">
                        {message}
                    </p>
                </div>

                {/* Analysis steps */}
                <div className="mt-8 flex flex-wrap justify-center gap-4">
                    {['Detecting', 'Identifying', 'Calculating', 'Reporting'].map((step, index) => (
                        <div
                            key={step}
                            className="flex items-center gap-2 rounded-full bg-white/70 px-3 py-1.5 text-xs font-medium text-slate-600 shadow-sm"
                        >
                            <span className={`flex h-5 w-5 items-center justify-center rounded-full bg-cyan-100 text-cyan-600 ${index === 0 ? 'animate-spin' : ''}`} style={{ animationDuration: '2s' }}>
                                {index === 0 ? (
                                    <svg className="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                ) : (
                                    <svg className="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                                    </svg>
                                )}
                            </span>
                            {step}
                        </div>
                    ))}
                </div>
            </div>

            {/* Progress bar */}
            <div className="mt-6 overflow-hidden rounded-full bg-slate-100">
                <div className="h-1.5 animate-progress rounded-full bg-gradient-to-r from-cyan-400 via-cyan-500 to-cyan-600" />
            </div>
        </div>
    );
};

export default AiScanningOverlay;