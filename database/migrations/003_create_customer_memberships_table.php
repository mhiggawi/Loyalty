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
        Schema::create('customer_memberships', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->foreignId('global_customer_id')
                ->constrained('global_customers')
                ->onDelete('cascade');

            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->onDelete('cascade');

            // Points Information
            $table->integer('current_points')->default(0);
            $table->integer('total_points_earned')->default(0);
            $table->integer('total_points_redeemed')->default(0);

            // Visit Statistics
            $table->integer('total_visits')->default(0);
            $table->decimal('total_spent', 10, 2)->default(0);

            // Tier Information
            $table->enum('tier_level', [
                'bronze',
                'silver',
                'gold',
                'platinum'
            ])->default('bronze');

            $table->timestamp('tier_upgraded_at')->nullable();

            // Membership Status
            $table->enum('membership_status', [
                'active',
                'suspended',
                'blocked'
            ])->default('active');

            // QR Code
            $table->string('qr_code_hash', 100)->unique();

            // Timestamps
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamp('last_visit_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Unique constraint: one membership per customer per tenant
            $table->unique(['global_customer_id', 'tenant_id']);

            // Indexes for performance
            $table->index('tenant_id');
            $table->index('tier_level');
            $table->index('membership_status');
            $table->index('qr_code_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_memberships');
    }
};
