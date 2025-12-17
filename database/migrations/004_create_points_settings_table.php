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
        Schema::create('points_settings', function (Blueprint $table) {
            $table->id();

            // Tenant Relationship
            $table->foreignId('tenant_id')
                ->unique()
                ->constrained('tenants')
                ->onDelete('cascade');

            // Points Configuration
            $table->decimal('currency_to_points_ratio', 10, 2)->default(1.00);
            // Example: 1.00 = 1 JOD = 1 Point
            // Example: 10.00 = 1 JOD = 10 Points

            // Points Expiration
            $table->integer('points_expiry_months')->nullable();
            // NULL = points never expire
            // 6 = points expire after 6 months
            // 12 = points expire after 12 months

            // Redemption Settings
            $table->boolean('allow_partial_redemption')->default(true);
            $table->integer('min_points_for_redemption')->default(0);

            // Welcome Bonus
            $table->integer('welcome_bonus_points')->default(0);

            // Birthday Bonus
            $table->integer('birthday_bonus_points')->default(0);

            // Referral Bonus
            $table->integer('referrer_bonus_points')->default(0);
            $table->integer('referee_bonus_points')->default(0);

            $table->timestamps();

            // Index
            $table->index('tenant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('points_settings');
    }
};
