<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->enum('workflow_status', ['pending', 'proposed', 'contracted', 'in_progress', 'completed'])
                ->default('pending')
                ->after('status_id');
            $table->foreignId('accepted_by_id')
                ->nullable()
                ->constrained('plumber_profiles')
                ->nullOnDelete()
                ->after('service_type_id');
            $table->jsonb('contract_terms')->nullable()->after('accepted_by_id');
            $table->string('contract_start_code', 8)->nullable()->after('contract_terms');
            $table->timestamp('contracted_at')->nullable()->after('contract_start_code');
        });

        Schema::create('booking_proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('plumber_profile_id')->constrained('plumber_profiles')->cascadeOnDelete();
            $table->integer('base_fee');
            $table->integer('material_cost');
            $table->integer('eta_minutes');
            $table->jsonb('proposal_terms')->nullable();
            $table->enum('status', ['pending', 'proposed', 'expired', 'accepted', 'rejected'])
                ->default('pending');
            $table->timestamps();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE INDEX booking_proposals_booking_status_idx ON booking_proposals USING BTREE (booking_id, status)');
        } else {
            DB::statement('CREATE INDEX booking_proposals_booking_status_idx ON booking_proposals (booking_id, status)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_proposals');

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['accepted_by_id']);
            $table->dropColumn(['workflow_status', 'accepted_by_id', 'contract_terms', 'contract_start_code', 'contracted_at']);
        });
    }
};
