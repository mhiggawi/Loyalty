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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();

            // Tenant Relationship
            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->onDelete('cascade');

            // Customer Relationship (nullable for broadcast notifications)
            $table->foreignId('customer_membership_id')
                ->nullable()
                ->constrained('customer_memberships')
                ->onDelete('cascade');

            // Notification Type
            $table->enum('type', [
                'points_earned',      // "You earned 50 points!"
                'tier_upgrade',       // "Congratulations! You're now Gold!"
                'reward_available',   // "New reward available: Free Coffee"
                'points_expiring',    // "100 points expiring in 7 days"
                'redemption_approved',// "Your redemption was approved"
                'redemption_rejected',// "Your redemption was rejected"
                'birthday',           // "Happy Birthday! Here's a bonus"
                'anniversary',        // "Happy Anniversary! 1 year with us"
                'custom'              // Custom merchant notification
            ]);

            // Titles (Multi-language)
            $table->string('title_ar')->nullable();
            $table->string('title_en')->nullable();

            // Messages (Multi-language)
            $table->text('message_ar')->nullable();
            $table->text('message_en')->nullable();

            // Action Data (JSON)
            $table->json('action_data')->nullable();
            // Example: {"reward_id": 5, "screen": "RewardDetails"}

            // Status
            $table->boolean('is_read')->default(false);

            // Channels Sent
            $table->boolean('sent_push')->default(false);
            $table->boolean('sent_email')->default(false);
            $table->boolean('sent_sms')->default(false);

            // Priority
            $table->enum('priority', ['low', 'normal', 'high'])->default('normal');

            // Scheduled Sending
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('tenant_id');
            $table->index('customer_membership_id');
            $table->index('type');
            $table->index('is_read');
            $table->index('sent_at');
            $table->index('scheduled_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
