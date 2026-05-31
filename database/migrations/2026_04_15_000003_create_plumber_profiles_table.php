<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plumber_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->json('service_type_ids')->nullable();
            $table->boolean('is_available')->default(false);
            $table->timestamp('available_since')->nullable();
            $table->string('availability_notes')->nullable();
            $table->boolean('verified')->default(false);
            $table->double('rating')->default(0);
            $table->timestamps();
        });

        // Add location column based on database driver
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE plumber_profiles ADD COLUMN location geography(POINT,4326)');
            DB::statement('CREATE INDEX plumber_profiles_location_gix ON plumber_profiles USING GIST(location)');

            Schema::table('plumber_profiles', function (Blueprint $table) {
                $table->decimal('latitude', 10, 8)->nullable();
                $table->decimal('longitude', 11, 8)->nullable();
            });
        } else {
            Schema::table('plumber_profiles', function (Blueprint $table) {
                $table->decimal('latitude', 10, 8)->nullable();
                $table->decimal('longitude', 11, 8)->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('plumber_profiles');
    }
};
