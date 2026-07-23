<?php

namespace App\Services\AI\Tools;

use App\Models\PlumberProfile;
use App\Models\Review;
use Carbon\Carbon;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class GetPlumberHistoryTool implements Tool
{
    /**
     * Get the tool's name.
     */
    public function name(): string
    {
        return 'get_plumber_history';
    }

    /**
     * Get the tool's description.
     */
    public function description(): string
    {
        return 'Retrieve a plumber\'s work history, including completed jobs count, average rating, recent reviews, and experience metrics.';
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
                    'description' => 'The ID of the plumber to retrieve history for',
                ],
            ],
            'required' => ['plumber_id'],
        ];
    }

    /**
     * Execute the tool.
     */
    public function execute(array $arguments): string
    {
        $plumberId = $arguments['plumber_id'];

        $plumber = PlumberProfile::find($plumberId);
        if (! $plumber) {
            return json_encode([
                'status' => 'error',
                'message' => 'Plumber not found',
            ]);
        }

        // Count completed bookings
        $completedBookings = $plumber->bookings()
            ->whereIn('workflow_status', ['completed', 'in_progress'])
            ->count();

        // Get reviews and average rating
        $reviews = Review::where('plumber_profile_id', $plumberId)->get();
        $reviewCount = $reviews->count();
        $averageRating = $reviewCount > 0 ? round($reviews->avg('rating'), 2) : 0;

        // Get recent reviews
        $recentReviews = Review::where('plumber_profile_id', $plumberId)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($review) {
                return [
                    'rating' => $review->rating,
                    'comment' => $review->comment ?? 'No comment',
                    'date' => $review->created_at->toDateString(),
                ];
            })
            ->toArray();

        // Calculate experience level
        $experienceLevel = match (true) {
            $completedBookings < 5 => 'emerging',
            $completedBookings < 20 => 'developing',
            $completedBookings < 50 => 'experienced',
            default => 'expert',
        };

        // Calculate response time metric (availability history)
        // $availableSince = $plumber->available_since ? $plumber->available_since->diffInDays(now()) : null;
        $availableSince = null;

        if ($plumber->available_since) {
            $availableSince = Carbon::parse(
                $plumber->available_since
            )->diffInDays(now());
        }

        return json_encode([
            'status' => 'success',
            'plumber_id' => $plumberId,
            'name' => $plumber->user->name,
            'completed_jobs' => $completedBookings,
            'review_count' => $reviewCount,
            'average_rating' => $averageRating,
            'experience_level' => $experienceLevel,
            'days_available' => $availableSince,
            'recent_reviews' => $recentReviews,
            'profile_rating' => $plumber->rating ?? 0,
            'is_verified' => $plumber->verified,
            'confidence_score' => $this->calculateConfidence($completedBookings, $averageRating, $reviewCount),
        ]);
    }

    public function handle(Request $request): string
    {
        return $this->execute($request->all());
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'plumber_id' => $schema->integer()->description('The ID of the plumber to retrieve history for')->required(),
        ];
    }

    /**
     * Calculate confidence score based on history.
     */
    private function calculateConfidence(int $completedJobs, float $averageRating, int $reviewCount): float
    {
        // Base score from job count (0-0.4)
        $jobScore = min(0.4, ($completedJobs / 50) * 0.4);

        // Rating score (0-0.4)
        $ratingScore = ($averageRating / 5) * 0.4;

        // Review count score (0-0.2)
        $reviewScore = min(0.2, ($reviewCount / 20) * 0.2);

        return round($jobScore + $ratingScore + $reviewScore, 2);
    }
}
