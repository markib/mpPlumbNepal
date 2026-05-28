import { useState } from 'react';

import { diagnoseIssue } from '../services/aiApi';

import { AiDiagnosisResult } from '../types/ai';

export const useAiDiagnosis = () => {
    const [loading, setLoading] = useState(false);

    const [result, setResult] =
        useState<AiDiagnosisResult | null>(null);

    const analyze = async (message: string) => {
        setLoading(true);

        try {
            const data = await diagnoseIssue(message);

            setResult(data);

            return data;
        } finally {
            setLoading(false);
        }
    };

    return {
        loading,
        result,
        analyze,
    };
};