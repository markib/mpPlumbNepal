<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plumber_profiles', function (Blueprint $table) {
            $table->string('socket_id', 100)->nullable()->after('current_heading');
        });
    }

    public function down(): void
    {
        Schema::table('plumber_profiles', function (Blueprint $table) {
            $table->dropColumn('socket_id');
        });
    }
};