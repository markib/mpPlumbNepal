export interface AiDiagnosisResult {
    issue_type: string;

    urgency: string;

    estimated_price_min: number;

    estimated_price_max: number;

    recommended_service: string;

    confidence: number;

    summary: string;

    ai_diagnosis_id?: number;
}