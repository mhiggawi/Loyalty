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
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();

            // Business Information
            $table->string('business_name');
            $table->string('business_slug', 100)->unique();
            $table->enum('business_type', [
                'restaurant',
                'salon',
                'retail',
                'gym',
                'cafe',
                'other'
            ])->default('other');

            // Contact Information
            $table->string('email')->unique();
            $table->string('phone', 20)->nullable();

            // Branding
            $table->string('logo_url', 500)->nullable();
            $table->string('primary_color', 7)->default('#667eea');

            // Subscription Details
            $table->enum('subscription_plan', [
                'free_trial',
                'starter',
                'professional',
                'enterprise'
            ])->default('free_trial');

            $table->enum('subscription_status', [
                'trial',
                'active',
                'suspended',
                'cancelled'
            ])->default('trial');

            $table->dateTime('subscription_expires_at')->nullable();

            // Limits based on plan
            $table->integer('max_customers')->default(500);
            $table->integer('max_staff')->default(3);

            // Trial Period
            $table->dateTime('trial_ends_at')->nullable();

            // API Access
            $table->string('api_key', 100)->unique()->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index('business_slug');
            $table->index('subscription_status');
            $table->index('subscription_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
