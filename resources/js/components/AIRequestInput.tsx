import React, { useState, useEffect, useRef } from 'react';
import { initializeEcho } from '../bootstrap/reverb';
import { getAuthToken } from '../utils/auth';

interface AiResult {
  issue_type: string;
  urgency: string;
  estimated_price_min: number;
  estimated_price_max: number;
  recommended_service: string;
  confidence: number;
  summary: string;
  ai_diagnosis_id?: number;
}

interface Props {
  onAnalysisComplete: (result: AiResult | null) => void;
  onAnalysisStart: () => void;
  resetKey?: number;
}

export const AIRequestInput: React.FC<Props> = ({
  onAnalysisComplete,
  onAnalysisStart,
  resetKey = 0,
}) => {
  const [message, setMessage] = useState('');
  const [loading, setLoading] = useState(false);
  const [result, setResult] = useState<AiResult | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [pipelineId, setPipelineId] = useState<number | null>(null);
  const [steps, setSteps] = useState<string[]>([]);

  const echoRef = useRef<any>(null);
  const channelRef = useRef<any>(null);

  useEffect(() => {
    setMessage('');
    setResult(null);
    setError(null);
    setPipelineId(null);
    setSteps([]);
  }, [resetKey]);

  /**
   * =========================
   * REALTIME PIPELINE LISTENER
   * =========================
   */
  useEffect(() => {
    if (!pipelineId) return;

    const token = getAuthToken();
    if (!token) {
      console.warn('No auth token - skipping Echo init');
      return;
    }
    // ✅ INIT ECHO ONCE
    if (!echoRef.current) {
      echoRef.current = initializeEcho(token);
    }

    const echo = echoRef.current;

    const channelName = `pipeline.${pipelineId}`;
    const channel = echo.private(channelName);

    channelRef.current = channel;

    channel
      .listen('.pipeline.step.completed', (e: any) => {
        console.log('STEP:', e.step);

        setSteps((prev) => [...prev, e.step]);

      
      })
      .listen('.pipeline.completed', (e: any) => {
        console.log('DONE:', e.result);

        setLoading(false);
        setResult(e.result);
        onAnalysisComplete(e.result);

        // cleanup
        echo.leave(channelName);
      })
      .listen('pipeline.failed', (e: any) => {
        console.error('FAILED:', e.error);

        setLoading(false);
        setError(e.error || 'Pipeline failed');
        onAnalysisComplete(null);

        echo.leave(channelName);
      });

    // ✅ CLEANUP ON UNMOUNT / PIPELINE CHANGE
    return () => {
      if (echoRef.current && pipelineId) {
        echoRef.current.leave(channelName);
      }
    };
  }, [pipelineId]);

  /**
   * =========================
   * START PIPELINE
   * =========================
   */
  const handleAnalyze = async () => {
    if (!message.trim()) {
      setError('Please describe the issue.');
      return;
    }

    setLoading(true);
    setError(null);
    setResult(null);
    setSteps([]);

    onAnalysisStart();

    try {
      const token = getAuthToken();

      const response = await fetch('/api/v1/ai/pipeline/start', {
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

      // 💡 CRITICAL STEP: Fetch doesn't throw errors for 400 or 500 status codes.
      // Check if the server rejected the request before trying to parse JSON.
      if (!response.ok) {
        const errorText = await response.text();
        throw new Error(`Server Response Error (${response.status}): ${errorText}`);
      }
      const data = await response.json();
console.log('Pipeline start response:', data);
      if (!response.ok) {
        throw new Error(data.message || 'Failed to start pipeline');
      }

      // 🔥 only pipelineId needed
      setPipelineId(data.pipeline_id);
    } catch (err: any) {
      console.error(err);
      setError(err.message);
      setLoading(false);
      onAnalysisComplete(null);
    }
  };

  return (
    <div className="p-6 bg-slate-50 rounded-xl border border-slate-200">
      <h2 className="text-lg font-bold mb-2">AI Pipeline (Real-Time)</h2>

      <textarea
        className="w-full p-3 border rounded-lg mb-4"
        rows={3}
        value={message}
        onChange={(e) => setMessage(e.target.value)}
        placeholder="Describe plumbing issue..."
      />

      <button
        onClick={handleAnalyze}
        disabled={loading}
        className="px-6 py-2 bg-blue-600 text-white rounded-lg"
      >
        {loading ? 'Running Pipeline...' : 'Start AI Pipeline'}
      </button>

      {/* STEPS */}
      {steps.length > 0 && (
        <div className="mt-4 text-sm text-gray-600">
          <p className="font-bold">Pipeline Steps:</p>
          <ul>
            {steps.map((s, i) => (
              <li key={i}>✓ {s}</li>
            ))}
          </ul>
        </div>
      )}

      {/* RESULT */}
      {result && (
        <div className="mt-4 p-4 bg-white rounded-lg border">
          <h3 className="font-bold">{result.issue_type}</h3>
          <p>{result.summary}</p>
        </div>
      )}

      {/* ERROR */}
      {error && (
        <div className="mt-3 text-red-600 text-sm">
          {error}
        </div>
      )}
    </div>
  );
};