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
        Schema::create('redemptions', function (Blueprint $table) {
            $table->id();

            // Tenant Relationship
            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->onDelete('cascade');

            // Customer Relationship
            $table->foreignId('customer_membership_id')
                ->constrained('customer_memberships')
                ->onDelete('cascade');

            // Reward Relationship
            $table->foreignId('reward_id')
                ->constrained('rewards')
                ->onDelete('cascade');

            // Points Used
            $table->integer('points_used');

            // Redemption Status
            $table->enum('status', [
                'pending',    // Waiting for approval
                'approved',   // Approved, ready to use
                'rejected',   // Rejected by merchant
                'used',       // Customer used the reward
                'expired',    // Redemption expired
                'cancelled'   // Cancelled by customer or merchant
            ])->default('pending');

            // Unique Redemption Code
            $table->string('redemption_code', 20)->unique();
            // Example: RDM-ABC123

            // QR Code for verification
            $table->string('qr_code_hash', 100)->unique()->nullable();

            // Timestamps
            $table->timestamp('redeemed_at')->useCurrent();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            // Staff who approved/rejected/used
            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('staff')
                ->onDelete('set null');

            $table->foreignId('used_by')
                ->nullable()
                ->constrained('staff')
                ->onDelete('set null');

            // Notes (for rejection reason, etc.)
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('tenant_id');
            $table->index('customer_membership_id');
            $table->index('reward_id');
            $table->index('status');
            $table->index('redemption_code');
            $table->index('redeemed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('redemptions');
    }
};
