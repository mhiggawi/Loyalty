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
        Schema::table('tenants', function (Blueprint $table) {
            // Stripe customer and subscription IDs
            $table->string('stripe_customer_id')->nullable()->after('api_key');
            $table->string('stripe_subscription_id')->nullable()->after('stripe_customer_id');

            // Subscription dates
            $table->timestamp('subscription_started_at')->nullable()->after('trial_ends_at');
            $table->timestamp('subscription_ends_at')->nullable()->after('subscription_started_at');

            // Max rewards limit
            $table->integer('max_rewards')->nullable()->after('max_staff');

            // Contact information for Stripe
            $table->string('contact_email')->nullable()->after('email');
            $table->string('contact_phone')->nullable()->after('phone');

            // Indexes for Stripe fields
            $table->index('stripe_customer_id');
            $table->index('stripe_subscription_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropIndex(['stripe_customer_id']);
            $table->dropIndex(['stripe_subscription_id']);

            $table->dropColumn([
                'stripe_customer_id',
                'stripe_subscription_id',
                'subscription_started_at',
                'subscription_ends_at',
                'max_rewards',
                'contact_email',
                'contact_phone',
            ]);
        });
    }
};
