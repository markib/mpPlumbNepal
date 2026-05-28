<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->unique();
            $table->string('password');
            $table->enum('role', ['customer', 'plumber'])->default('customer');
            $table->enum('locale', ['en', 'ne'])->default('en');
            $table->enum('verification_status', ['unverified', 'submitted', 'approved', 'rejected'])->default('unverified');
            $table->text('verification_notes')->nullable();
            $table->boolean('verified_badge')->default(false);
            $table->timestamp('email_verified_at')->nullable();
            $table->string('remember_token')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
