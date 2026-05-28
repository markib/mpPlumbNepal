<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
        });

        DB::table('booking_statuses')->insert([
            ['id' => 1, 'name' => 'Pending', 'description' => 'Awaiting plumber acceptance'],
            ['id' => 2, 'name' => 'Accepted', 'description' => 'Booking accepted by plumber'],
            ['id' => 3, 'name' => 'En Route', 'description' => 'Plumber is on the way'],
            ['id' => 4, 'name' => 'Job Started', 'description' => 'Plumber has started the job'],
            ['id' => 5, 'name' => 'Completed', 'description' => 'Service completed'],
            ['id' => 6, 'name' => 'Cancelled', 'description' => 'Booking cancelled'],
        ]);

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();

            if (DB::getDriverName() === 'sqlite') {
                $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            } else {
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            }

            $table->foreignId('plumber_profile_id')->nullable()->constrained('plumber_profiles')->nullOnDelete();
            $table->foreignId('service_type_id')->constrained('service_types');
            $table->foreignId('status_id')->default(1)->constrained('booking_statuses');
            $table->enum('payment_method', ['esewa', 'khalti', 'ime_pay', 'cod']);
            $table->integer('amount');
            $table->boolean('is_emergency')->default(false);
            $table->string('landmark')->nullable();
            $table->string('ward_number')->nullable();
            $table->string('tole_name')->nullable();
            $table->text('service_notes')->nullable();
            $table->timestamps();
        });

        // Add location column based on database driver
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE bookings ADD COLUMN pickup_location geography(POINT,4326)');
            DB::statement('CREATE INDEX bookings_pickup_location_gix ON bookings USING GIST(pickup_location)');
        } else {
            // For SQLite and other databases, use regular columns
            Schema::table('bookings', function (Blueprint $table) {
                $table->decimal('pickup_latitude', 10, 8)->nullable();
                $table->decimal('pickup_longitude', 11, 8)->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('booking_statuses');
    }
};
