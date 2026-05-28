<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('plumber_profiles', function (Blueprint $table) {
            $table->timestamp('last_location_update')->nullable()->after('availability_notes');
            $table->decimal('location_accuracy', 8, 2)->nullable()->after('last_location_update');
            $table->decimal('current_speed', 5, 2)->nullable()->after('location_accuracy');
            $table->decimal('current_heading', 5, 2)->nullable()->after('current_speed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plumber_profiles', function (Blueprint $table) {
            $table->dropColumn(['last_location_update', 'location_accuracy', 'current_speed', 'current_heading']);
        });
    }
};
