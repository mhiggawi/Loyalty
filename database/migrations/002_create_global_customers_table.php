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
        Schema::create('global_customers', function (Blueprint $table) {
            $table->id();

            // Primary Identifiers
            $table->string('phone_number', 20)->unique();
            $table->string('email')->unique()->nullable();

            // Personal Information
            $table->string('full_name')->nullable();
            $table->date('date_of_birth')->nullable();

            // Authentication
            $table->string('password_hash')->nullable();

            // Mobile App Integration
            $table->string('device_token', 500)->nullable();

            // Preferences
            $table->enum('language', ['ar', 'en'])->default('ar');

            // Email Verification
            $table->timestamp('email_verified_at')->nullable();

            // Phone Verification
            $table->timestamp('phone_verified_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('phone_number');
            $table->index('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('global_customers');
    }
};
