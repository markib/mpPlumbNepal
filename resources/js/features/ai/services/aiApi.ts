import { getAuthToken } from '../../../utils/auth';

import type { AiDiagnosisResult } from '../types/ai';

/**
 * Start AI pipeline
 */
export const startPipeline = async (message: string) => {
    const token = getAuthToken();

    const res = await fetch('/api/v1/pipeline/start', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            Authorization: `Bearer ${token}`,
        },
        body: JSON.stringify({
            message: message.trim(),
        }),
    });

    const json = await res.json();

    if (!res.ok) {
        throw new Error(json.message || 'AI request failed');
    }

    return json; // { pipeline_id, status, message }
};

/**
 * Poll pipeline result until completed
 */
export const pollPipelineResult = async (
    pipelineId: number,
    onUpdate?: (result: AiDiagnosisResult | null) => void,
    interval = 2000,
    maxAttempts = 30
): Promise<AiDiagnosisResult> => {
    const token = getAuthToken();

    let attempts = 0;

    const fetchResult = async (): Promise<AiDiagnosisResult> => {
        const res = await fetch(`/api/v1/pipeline/${pipelineId}`, {
            headers: {
                Accept: 'application/json',
                Authorization: `Bearer ${token}`,
            },
        });

        const json = await res.json();

        if (!res.ok) {
            throw new Error(json.message || 'Failed to fetch pipeline result');
        }

        // still processing
        if (json.status !== 'completed') {
            attempts++;

            if (attempts >= maxAttempts) {
                throw new Error('AI processing timeout');
            }

            onUpdate?.(null);

            await new Promise((resolve) =>
                setTimeout(resolve, interval)
            );

            return fetchResult();
        }

        // completed
        const result = json.result;

        onUpdate?.(result);

        return result;
    };

    return fetchResult();
};

export const getPipelineResult = async (
    pipelineId: number
) => {

    const token = getAuthToken();

    const res = await fetch(
        `/api/v1/pipeline/${pipelineId}`,
        {
            headers: {
                Accept: 'application/json',
                Authorization: `Bearer ${token}`,
            },
        }
    );

    const json = await res.json();

    if (!res.ok) {
        throw new Error(
            json.message ||
            'Failed to load pipeline result'
        );
    }

    return json;
};

/**
 * Convenience helper to start an AI pipeline and wait for its result.
 */
export const diagnoseIssue = async (message: string) => {
    const start = await startPipeline(message);

    if (!start || !start.pipeline_id) {
        throw new Error('Failed to start AI pipeline');
    }

    const result = await pollPipelineResult(start.pipeline_id);

    return result as AiDiagnosisResult;
};