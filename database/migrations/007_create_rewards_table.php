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
        Schema::create('rewards', function (Blueprint $table) {
            $table->id();

            // Tenant Relationship
            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->onDelete('cascade');

            // Reward Titles (Multi-language)
            $table->string('title_ar');
            $table->string('title_en')->nullable();

            // Reward Descriptions (Multi-language)
            $table->text('description_ar')->nullable();
            $table->text('description_en')->nullable();

            // Reward Image
            $table->string('image_url', 500)->nullable();

            // Category
            $table->enum('category', [
                'drink',
                'food',
                'discount',
                'gift',
                'experience',
                'service',
                'other'
            ])->default('other');

            // Reward Type
            $table->enum('reward_type', [
                'free_product',         // Free coffee, free haircut
                'percentage_discount',  // 10% off, 20% off
                'fixed_discount',       // 5 JOD off, 10 JOD off
                'experience'            // VIP treatment, early access
            ]);

            // Discount Value (for discount types)
            $table->decimal('discount_value', 10, 2)->nullable();
            // For percentage_discount: 10.00 = 10%
            // For fixed_discount: 5.00 = 5 JOD off

            // Points Required
            $table->integer('points_required');

            // Stock Management
            $table->integer('stock')->nullable();
            // NULL = unlimited
            // 0 = out of stock
            // >0 = available quantity

            // Availability
            $table->boolean('is_active')->default(true);

            // Display Order (for sorting in app)
            $table->integer('display_order')->default(0);

            // Tier Restriction (optional)
            $table->enum('min_tier_required', [
                'bronze',
                'silver',
                'gold',
                'platinum'
            ])->nullable();

            // Terms and Conditions
            $table->text('terms_ar')->nullable();
            $table->text('terms_en')->nullable();

            // Validity Period
            $table->dateTime('valid_from')->nullable();
            $table->dateTime('valid_until')->nullable();

            // Statistics
            $table->integer('total_redemptions')->default(0);

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('tenant_id');
            $table->index('category');
            $table->index('reward_type');
            $table->index('is_active');
            $table->index('points_required');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rewards');
    }
};
