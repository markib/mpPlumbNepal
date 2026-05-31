export interface AiDiagnosisInsights {
    customer_explanation?: string;
    likely_cause?: string;
    risk_level?: 'low' | 'medium' | 'high' | 'critical';

    immediate_actions?: string[];
    diy_checks?: string[];
    required_tools?: string[];
    professional_steps?: string[];
    safety_precautions?: string[];
    preventive_maintenance?: string[];
}