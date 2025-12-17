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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();

            // Tenant Relationship
            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->onDelete('cascade');

            // Customer Relationship
            $table->foreignId('customer_membership_id')
                ->constrained('customer_memberships')
                ->onDelete('cascade');

            // Transaction Type
            $table->enum('type', [
                'earn',              // Earned points from purchase
                'redeem',            // Redeemed points for reward
                'bonus',             // Bonus points (birthday, anniversary)
                'referral',          // Referral bonus
                'manual_add',        // Manually added by staff/admin
                'manual_subtract',   // Manually subtracted by staff/admin
                'expire',            // Points expired
                'adjustment'         // System adjustment
            ]);

            // Points Amount
            $table->integer('points');
            // Positive for earn/bonus/referral/manual_add
            // Negative for redeem/manual_subtract/expire

            // Purchase Information (for 'earn' type)
            $table->decimal('amount', 10, 2)->nullable();
            // Purchase amount in currency (e.g., 50.00 JOD)

            // Description
            $table->string('description', 500)->nullable();
            // "Purchase: 50 JOD"
            // "Redeemed: Free Coffee"
            // "Birthday Bonus"
            // "Manual adjustment by Admin"

            // Reference to related records
            $table->unsignedBigInteger('reference_id')->nullable();
            // redemption_id, reward_id, etc.

            $table->string('reference_type', 50)->nullable();
            // "redemption", "reward", "referral"

            // Staff who processed (for manual transactions)
            $table->foreignId('staff_id')
                ->nullable()
                ->constrained('staff')
                ->onDelete('set null');

            // Balance after transaction
            $table->integer('balance_after')->default(0);

            // Metadata (JSON for additional info)
            $table->json('metadata')->nullable();

            $table->timestamps();

            // Indexes for performance
            $table->index('tenant_id');
            $table->index('customer_membership_id');
            $table->index('type');
            $table->index('created_at');
            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
