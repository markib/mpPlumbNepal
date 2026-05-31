<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plumber_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('plumber_profile_id')->constrained('plumber_profiles')->cascadeOnDelete();

            // Rating fields
            $table->tinyInteger('rating'); // 1-5 stars
            $table->text('comment')->nullable();

            // PostgreSQL 18 optimized indexes
            $table->index('rating');
            $table->index(['plumber_profile_id', 'created_at']);

            // Prevent duplicate ratings per booking/user
            $table->unique(['booking_id', 'user_id'], 'plumber_ratings_unique_booking_user');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plumber_ratings');
    }
};
