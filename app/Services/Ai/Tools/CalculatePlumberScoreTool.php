<?php

namespace App\Services\AI\Tools;

use App\Models\PlumberProfile;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class CalculatePlumberScoreTool implements Tool
{
    /**
     * Get the tool's name.
     */
    public function name(): string
    {
        return 'calculate_plumber_score';
    }

    /**
     * Get the tool's description.
     */
    public function description(): string
    {
        return 'Calculate a composite match score for a plumber based on rating, distance, work history, and skill match.';
    }

    /**
     * Get the tool's input schema.
     */
    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'plumber_id' => [
                    'type' => 'integer',
                    'description' => 'The ID of the plumber',
                ],
                'distance_km' => [
                    'type' => 'number',
                    'description' => 'Distance from job location in kilometers',
                ],
                'rating' => [
                    'type' => 'number',
                    'description' => 'Plumber\'s rating (0-5)',
                ],
                'completed_jobs' => [
                    'type' => 'integer',
                    'description' => 'Number of completed jobs',
                ],
                'average_job_rating' => [
                    'type' => 'number',
                    'description' => 'Average rating from jobs',
                ],
                'skill_match_percentage' => [
                    'type' => 'number',
                    'description' => 'Percentage of skills matching job requirements (0-100)',
                ],
                'is_available' => [
                    'type' => 'boolean',
                    'description' => 'Whether plumber is currently available',
                ],
            ],
            'required' => ['plumber_id', 'distance_km', 'rating', 'completed_jobs', 'average_job_rating', 'skill_match_percentage'],
        ];
    }

    /**
     * Execute the tool.
     */
    public function execute(array $arguments): string
    {
        $plumberId = $arguments['plumber_id'];
        $distanceKm = $arguments['distance_km'];
        $rating = $arguments['rating'];
        $completedJobs = $arguments['completed_jobs'];
        $averageJobRating = $arguments['average_job_rating'];
        $skillMatchPercentage = $arguments['skill_match_percentage'];
        $isAvailable = $arguments['is_available'] ?? true;

        $plumber = PlumberProfile::find($plumberId);
        if (! $plumber) {
            return json_encode([
                'status' => 'error',
                'message' => 'Plumber not found',
            ]);
        }

        // Calculate individual scores
        $ratingScore = ($rating / 5) * 100; // 0-100
        $distanceScore = $this->calculateDistanceScore($distanceKm); // 0-100
        $experienceScore = $this->calculateExperienceScore($completedJobs, $averageJobRating); // 0-100
        $availabilityScore = $isAvailable ? 100 : 50; // 50-100

        // Composite score with weights: rating (30%), distance (25%), experience (25%), skills (15%), availability (5%)
        $compositeScore = (
            ($ratingScore * 0.30) +
            ($distanceScore * 0.25) +
            ($experienceScore * 0.25) +
            ($skillMatchPercentage * 0.15) +
            ($availabilityScore * 0.05)
        );

        $compositeScore = round(max(0, min(100, $compositeScore)), 2);

        return json_encode([
            'status' => 'success',
            'plumber_id' => $plumberId,
            'composite_score' => $compositeScore,
            'breakdown' => [
                'rating_score' => round($ratingScore, 2),
                'distance_score' => round($distanceScore, 2),
                'experience_score' => round($experienceScore, 2),
                'skill_match_score' => round($skillMatchPercentage, 2),
                'availability_score' => round($availabilityScore, 2),
            ],
            'rank_factor' => $this->calculateRankFactor($compositeScore),
        ]);
    }

    public function handle(Request $request): string
    {
        return $this->execute($request->all());
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'plumber_id' => $schema->integer()->description('The ID of the plumber')->required(),
            'distance_km' => $schema->number()->description('Distance from job location in kilometers')->required(),
            'rating' => $schema->number()->description('Plumber rating from 0 to 5')->required(),
            'completed_jobs' => $schema->integer()->description('Number of completed jobs')->required(),
            'average_job_rating' => $schema->number()->description('Average rating from completed jobs')->required(),
            'skill_match_percentage' => $schema->number()->description('Skill match percentage from 0 to 100')->required(),
            'is_available' => $schema->boolean()->description('Whether plumber is currently available'),
        ];
    }

    /**
     * Calculate distance score (closer = higher score).
     */
    private function calculateDistanceScore(float $distanceKm): float
    {
        // At 0 km: 100, at 15 km: 50, at 30+ km: 0
        if ($distanceKm <= 0) {
            return 100;
        }
        if ($distanceKm >= 30) {
            return 0;
        }

        return max(0, 100 - ($distanceKm / 30 * 100));
    }

    /**
     * Calculate experience score based on completed jobs and ratings.
     */
    private function calculateExperienceScore(int $completedJobs, float $averageJobRating): float
    {
        // Job count score (0-50): max at 50+ jobs
        $jobScore = min(50, ($completedJobs / 50) * 50);

        // Rating consistency score (0-50): weighted by job rating
        $ratingScore = ($averageJobRating / 5) * 50;

        return $jobScore + $ratingScore;
    }

    /**
     * Determine rank factor based on composite score.
     */
    private function calculateRankFactor(float $score): string
    {
        return match (true) {
            $score >= 85 => 'excellent',
            $score >= 70 => 'good',
            $score >= 55 => 'fair',
            $score >= 40 => 'emerging',
            default => 'limited',
        };
    }
}
