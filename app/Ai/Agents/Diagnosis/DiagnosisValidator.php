<?php

namespace App\Ai\Agents\Diagnosis;

class DiagnosisValidator
{
    public function handle(array $diagnosis): array
    {
        $issueType = strtolower(
            $diagnosis['issue_type'] ?? 'other'
        );

        $summary = strtolower(
            $diagnosis['summary'] ?? ''
        );

        $customerExplanation = strtolower(
            $diagnosis['customer_explanation'] ?? ''
        );

        $likelyCause = strtolower(
            $diagnosis['likely_cause'] ?? ''
        );

        $combinedText = implode(' ', [
            $summary,
            $customerExplanation,
            $likelyCause,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Smart Issue Detection Rules
        |--------------------------------------------------------------------------
        */

        if (
            str_contains($combinedText, 'leak') ||
            str_contains($combinedText, 'drip') ||
            str_contains($combinedText, 'water under sink') ||
            str_contains($combinedText, 'pipe leaking')
        ) {
            $issueType = 'pipe_leak';
        }

        if (
            str_contains($combinedText, 'burst pipe') ||
            str_contains($combinedText, 'flooding') ||
            str_contains($combinedText, 'water everywhere')
        ) {
            $issueType = 'pipe_burst';
        }

        if (
            str_contains($combinedText, 'drain clogged') ||
            str_contains($combinedText, 'slow drain') ||
            str_contains($combinedText, 'blocked drain')
        ) {
            $issueType = 'clogged_drain';
        }

        if (
            str_contains($combinedText, 'toilet overflow') ||
            str_contains($combinedText, 'toilet blocked')
        ) {
            $issueType = 'toilet_issue';
        }

        /*
        |--------------------------------------------------------------------------
        | Normalize Urgency
        |--------------------------------------------------------------------------
        */

        $urgency = strtolower(
            $diagnosis['urgency'] ?? 'medium'
        );

        if (! in_array($urgency, [
            'low',
            'medium',
            'high',
        ])) {
            $urgency = 'medium';
        }

        /*
        |--------------------------------------------------------------------------
        | Normalize Confidence
        |--------------------------------------------------------------------------
        */

        $confidence = (float) (
            $diagnosis['confidence'] ?? 0
        );

        $confidence = max(
            0,
            min(1, $confidence)
        );

        /*
        |--------------------------------------------------------------------------
        | Normalize Pricing
        |--------------------------------------------------------------------------
        */

        $priceMin = max(
            0,
            (int) (
                $diagnosis['estimated_price_min'] ?? 0
            )
        );

        $priceMax = max(
            $priceMin,
            (int) (
                $diagnosis['estimated_price_max'] ?? 0
            )
        );

        /*
        |--------------------------------------------------------------------------
        | Smart Pricing + Service Rules
        |--------------------------------------------------------------------------
        */

        $service = $diagnosis['recommended_service']
            ?? 'General Plumbing Service';

        switch ($issueType) {

            case 'pipe_leak':

                $service = 'Leak Repair';

                $priceMin = max($priceMin, 1500);
                $priceMax = max($priceMax, 5000);

                break;

            case 'pipe_burst':

                $service = 'Emergency Pipe Repair';

                $priceMin = max($priceMin, 5000);
                $priceMax = max($priceMax, 25000);

                $urgency = 'high';

                break;

            case 'clogged_drain':

                $service = 'Drain Cleaning';

                $priceMin = max($priceMin, 1000);
                $priceMax = max($priceMax, 4000);

                break;

            case 'toilet_issue':

                $service = 'Toilet Repair';

                $priceMin = max($priceMin, 1200);
                $priceMax = max($priceMax, 6000);

                break;
        }

        /*
        |--------------------------------------------------------------------------
        | Emergency Detection
        |--------------------------------------------------------------------------
        */

        $isEmergency = in_array($issueType, [
            'pipe_burst',
            'sewage_backup',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Reliability Detection
        |--------------------------------------------------------------------------
        */

        $isReliable = $confidence >= config(
            'ai.confidence_threshold',
            0.4
        );

        /*
        |--------------------------------------------------------------------------
        | Plumbing Related Detection
        |--------------------------------------------------------------------------
        */

        $isPlumbingRelated = ! (
            $confidence === 0.0 &&
            empty($service)
        );

        /*
        |--------------------------------------------------------------------------
        | Risk Level Normalization
        |--------------------------------------------------------------------------
        */

        $riskLevel = strtolower(
            $diagnosis['risk_level'] ?? 'medium'
        );

        if (! in_array($riskLevel, [
            'low',
            'medium',
            'high',
            'critical',
        ])) {
            $riskLevel = 'medium';
        }

        /*
        |--------------------------------------------------------------------------
        | Safe Array Defaults
        |--------------------------------------------------------------------------
        */

        $immediateActions = is_array(
            $diagnosis['immediate_actions'] ?? null
        )
            ? $diagnosis['immediate_actions']
            : [];

        $diyChecks = is_array(
            $diagnosis['diy_checks'] ?? null
        )
            ? $diagnosis['diy_checks']
            : [];

        $requiredTools = is_array(
            $diagnosis['required_tools'] ?? null
        )
            ? $diagnosis['required_tools']
            : [];

        $professionalSteps = is_array(
            $diagnosis['professional_steps'] ?? null
        )
            ? $diagnosis['professional_steps']
            : [];

        $safetyPrecautions = is_array(
            $diagnosis['safety_precautions'] ?? null
        )
            ? $diagnosis['safety_precautions']
            : [];

        $preventiveMaintenance = is_array(
            $diagnosis['preventive_maintenance'] ?? null
        )
            ? $diagnosis['preventive_maintenance']
            : [];

        /*
        |--------------------------------------------------------------------------
        | Final Output
        |--------------------------------------------------------------------------
        */

        return array_merge($diagnosis, [

            'issue_type' => $issueType,

            'urgency' => $isEmergency
                ? 'high'
                : $urgency,

            'estimated_price_min' => $priceMin,

            'estimated_price_max' => $priceMax,

            'recommended_service' => $service,

            'confidence' => $confidence,

            'summary' => trim(
                $diagnosis['summary']
                    ?? 'No diagnosis summary available.'
            ),

            'customer_explanation' => trim(
                $diagnosis['customer_explanation']
                    ?? ''
            ),

            'likely_cause' => trim(
                $diagnosis['likely_cause']
                    ?? ''
            ),

            'risk_level' => $riskLevel,

            'immediate_actions' => $immediateActions,

            'diy_checks' => $diyChecks,

            'required_tools' => $requiredTools,

            'professional_steps' => $professionalSteps,

            'safety_precautions' => $safetyPrecautions,

            'preventive_maintenance' => $preventiveMaintenance,

            'is_reliable' => $isReliable,

            'is_emergency' => $isEmergency,

            'is_plumbing_related' => $isPlumbingRelated,

            'requires_manual_review' => ! $isReliable,

            'validated_at' => now()->toISOString(),
        ]);
    }
}
