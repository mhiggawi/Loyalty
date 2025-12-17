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
        Schema::create('staff', function (Blueprint $table) {
            $table->id();

            // Tenant Relationship
            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->onDelete('cascade');

            // Branch (for multi-branch support in Phase 2)
            $table->unsignedBigInteger('branch_id')->nullable();
            // Will be added in Phase 2

            // Personal Information
            $table->string('full_name');
            $table->string('email')->unique()->nullable();
            $table->string('phone', 20)->nullable();

            // Authentication
            $table->string('password_hash');

            // Role
            $table->enum('role', [
                'admin',    // Full access to merchant dashboard
                'manager',  // Manage staff, view reports
                'staff'     // Only scan QR, process redemptions
            ])->default('staff');

            // Permissions (JSON)
            $table->json('permissions')->nullable();
            // Example: {"can_scan_qr": true, "can_add_points": true, "can_redeem": true, "can_view_reports": false}

            // Profile Image
            $table->string('profile_image_url', 500)->nullable();

            // Status
            $table->boolean('is_active')->default(true);

            // Last Login
            $table->timestamp('last_login_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('tenant_id');
            $table->index('role');
            $table->index('is_active');
            $table->index('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};
