<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->jsonb('job_order_json')->nullable()->after('contract_terms');
            $table->timestamp('job_started_at')->nullable()->after('contracted_at');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['job_order_json', 'job_started_at']);
        });
    }
};
