import { AiDiagnosisCore } from "./ai-diagnosis.core";
import { AiDiagnosisInsights } from "./ai-diagnosis.insights";

export type AiDiagnosisResult = AiDiagnosisCore &
    AiDiagnosisInsights & {
        estimated_price_min: number;
        estimated_price_max: number;
        recommended_service: string;
        ai_diagnosis_id?: number;
    };