<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'citizenship_verified')) {
                $table->boolean('citizenship_verified')->default(false)->after('verified_badge');
            }
        });

        Schema::table('plumber_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('plumber_profiles', 'is_online')) {
                $table->boolean('is_online')->default(false)->after('is_available');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('citizenship_verified');
        });

        Schema::table('plumber_profiles', function (Blueprint $table) {
            $table->dropColumn('is_online');
        });
    }
};
