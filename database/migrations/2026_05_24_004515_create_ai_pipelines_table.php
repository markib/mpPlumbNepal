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
        Schema::create('ai_pipelines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->nullable()->index();

            $table->string('status')->default('processing');
            // processing | completed | failed

            $table->json('input')->nullable();
            $table->json('result')->nullable();

            $table->string('current_step')->nullable();
            $table->text('error')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_pipelines');
    }
};
