<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->timestamp('broadcast_expires_at')->nullable()->after('ai_diagnosis_id');
            $table->string('broadcast_status', 30)->default('pending')->after('broadcast_expires_at');
            $table->decimal('min_rating_required', 3, 2)->default(3.50)->after('broadcast_status');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['broadcast_expires_at', 'broadcast_status', 'min_rating_required']);
        });
    }
};