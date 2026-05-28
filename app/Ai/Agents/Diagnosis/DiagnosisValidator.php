<?php

namespace App\Ai\Agents\Diagnosis;

class DiagnosisValidator
{
    public function handle(array $diagnosis): array
    {
        $issueType = $diagnosis['issue_type'] ?? 'other';
        $urgency = strtolower($diagnosis['urgency'] ?? 'medium');
        $confidence = (float) ($diagnosis['confidence'] ?? 0);

        if (! in_array($urgency, ['low', 'medium', 'high'])) {
            $urgency = 'medium';
        }

        $confidence = max(0, min(1, $confidence));

        $priceMin = max(
            0,
            (int) ($diagnosis['estimated_price_min'] ?? 0)
        );

        $priceMax = max(
            $priceMin,
            (int) ($diagnosis['estimated_price_max'] ?? 0)
        );

        $isReliable = $confidence >= config(
            'ai.confidence_threshold',
            0.4
        );

        $isEmergency = in_array($issueType, [
            'pipe_burst',
            'sewage_backup',
        ]);

        $isPlumbingRelated = ! (
            $confidence === 0.0 &&
            empty($diagnosis['recommended_service'])
        );

        return array_merge($diagnosis, [
            'issue_type' => $issueType,

            'urgency' => $isEmergency
                ? 'high'
                : $urgency,

            'estimated_price_min' => $priceMin,

            'estimated_price_max' => $priceMax,

            'recommended_service' => $diagnosis['recommended_service']
                ?? 'General Plumbing Service',

            'confidence' => $confidence,

            'summary' => trim(
                $diagnosis['summary']
                    ?? 'No diagnosis summary available.'
            ),

            'is_reliable' => $isReliable,
            'is_emergency' => $isEmergency,
            'is_plumbing_related' => $isPlumbingRelated,
            'requires_manual_review' => ! $isReliable,
            'validated_at' => now()->toISOString(),
        ]);
    }
}
