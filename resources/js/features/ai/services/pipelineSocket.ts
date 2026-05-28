import { initializeEcho } from '../../../bootstrap/reverb';
import { getAuthToken } from '../../../utils/auth';

// 💡 Define a type-safe contract matching your broadcast payloads
interface PipelineCompletedPayload {
    pipelineId: number;
    status: string;
}

export function subscribeToPipeline(
    pipelineId: number,
    callbacks: {
        onStepCompleted?: (
            stepName: string,
            data: any
        ) => void;

        // 💡 FIX: Expect the object payload contract instead of just a raw number
        onCompleted?: (
            payload: PipelineCompletedPayload
        ) => void;
    }
) {
    const token = getAuthToken();

    const echo = initializeEcho(
        token || undefined
    );

    console.log(
        'Subscribing to pipeline:',
        pipelineId
    );

    const channel = echo.private(
        `pipeline.${pipelineId}`
    );

    channel.subscribed(() => {
        console.log(
            '✅ Reverb subscribed successfully'
        );
    });

    channel.error((error: any) => {
        console.error(
            '❌ Reverb subscription error',
            error
        );
    });

    channel.listen(
        '.pipeline.step.completed',
        (event: any) => {
            console.log(
                '📦 Step Event Received',
                event
            );

            callbacks.onStepCompleted?.(
                event.stepName,
                event.data
            );
        }
    );

    channel.listen(
        '.pipeline.completed',
        (event: PipelineCompletedPayload) => {
            console.log(
                '✅ Pipeline Completed Signal Captured',
                event
            );

            // 💡 FIX: Pass the complete object down to your component handler
            callbacks.onCompleted?.(event);
        }
    );

    return channel;
}