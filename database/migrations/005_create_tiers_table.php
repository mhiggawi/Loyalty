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
        Schema::create('tiers', function (Blueprint $table) {
            $table->id();

            // Tenant Relationship
            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->onDelete('cascade');

            // Tier Information
            $table->string('name', 100);
            // e.g., "Bronze", "Silver", "Gold", "Platinum"

            $table->enum('level', [
                'bronze',
                'silver',
                'gold',
                'platinum'
            ]);

            // Tier Requirements
            $table->integer('min_points')->default(0);
            // Bronze: 0
            // Silver: 501
            // Gold: 1501
            // Platinum: 3001

            // Tier Benefits
            $table->decimal('points_multiplier', 3, 2)->default(1.00);
            // Bronze: 1.00 (1x)
            // Silver: 1.50 (1.5x)
            // Gold: 2.00 (2x)
            // Platinum: 3.00 (3x)

            $table->text('benefits')->nullable();
            // JSON or text describing benefits
            // Example: "10% discount on all purchases, Early access to new products"

            // Visual Customization
            $table->string('icon', 50)->nullable();
            // Emoji or icon name: "🥉", "🥈", "🥇", "💎"

            $table->string('color', 7)->nullable();
            // Hex color code: #CD7F32, #C0C0C0, #FFD700, #E5E4E2

            // Display Order
            $table->integer('display_order')->default(0);

            // Active Status
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Indexes
            $table->index('tenant_id');
            $table->index('level');
            $table->index('min_points');

            // Unique constraint: one tier per level per tenant
            $table->unique(['tenant_id', 'level']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tiers');
    }
};
