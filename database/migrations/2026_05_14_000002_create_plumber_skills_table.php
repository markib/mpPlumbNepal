<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plumber_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plumber_profile_id')->constrained()->onDelete('cascade');
            $table->foreignId('skill_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['plumber_profile_id', 'skill_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plumber_skills');
    }
};
