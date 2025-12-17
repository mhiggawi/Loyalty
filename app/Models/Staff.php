<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Support\Collection;

class Staff extends Authenticatable implements FilamentUser, HasTenants
{
    use HasFactory, SoftDeletes, HasApiTokens, Notifiable;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'full_name',
        'email',
        'phone',
        'password_hash',
        'role',
        'permissions',
        'profile_image_url',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected $casts = [
        'permissions' => 'array',
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
    ];

    protected $appends = ['name'];

    /**
     * Get the password attribute name.
     */
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    /**
     * Get the name attribute (accessor for full_name).
     */
    public function getNameAttribute(): string
    {
        return $this->attributes['full_name'] ?? $this->attributes['email'] ?? '';
    }

    /**
     * Get the tenant this staff belongs to.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get transactions created by this staff.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get redemptions approved by this staff.
     */
    public function approvedRedemptions(): HasMany
    {
        return $this->hasMany(Redemption::class, 'approved_by');
    }

    /**
     * Get redemptions used by this staff.
     */
    public function usedRedemptions(): HasMany
    {
        return $this->hasMany(Redemption::class, 'used_by');
    }

    /**
     * Check if staff has permission.
     */
    public function hasPermission(string $permission): bool
    {
        if ($this->role === 'admin') {
            return true; // Admin has all permissions
        }

        $permissions = $this->permissions ?? [];
        return $permissions[$permission] ?? false;
    }

    /**
     * Check if staff can scan QR codes.
     */
    public function canScanQR(): bool
    {
        return $this->hasPermission('can_scan_qr');
    }

    /**
     * Check if staff can add points.
     */
    public function canAddPoints(): bool
    {
        return $this->hasPermission('can_add_points');
    }

    /**
     * Check if staff can redeem rewards.
     */
    public function canRedeem(): bool
    {
        return $this->hasPermission('can_redeem');
    }

    /**
     * Check if staff can view reports.
     */
    public function canViewReports(): bool
    {
        return $this->hasPermission('can_view_reports');
    }

    /**
     * Check if staff can manage other staff.
     */
    public function canManageStaff(): bool
    {
        return $this->hasPermission('can_manage_staff');
    }

    /**
     * Check if staff is admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if staff is manager.
     */
    public function isManager(): bool
    {
        return $this->role === 'manager';
    }

    /**
     * Update last login timestamp.
     */
    public function updateLastLogin(): void
    {
        $this->last_login_at = now();
        $this->save();
    }

    /**
     * Scope: Active staff only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Staff for tenant.
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope: Staff by role.
     */
    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Determine if the user can access the Filament panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Only allow access to merchant panel
        return $panel->getId() === 'merchant' && $this->is_active;
    }

    /**
     * Get the user's name for Filament.
     */
    public function getFilamentName(): string
    {
        return $this->full_name ?? $this->email;
    }

    /**
     * Get the tenant that owns this staff (for Filament multi-tenancy).
     */
    public function getTenant(): ?Tenant
    {
        return $this->tenant;
    }

    /**
     * Get the tenants that the user can access (for Filament multi-tenancy).
     * This method is required for multi-tenancy.
     */
    public function getTenants(Panel $panel): Collection
    {
        // Staff members belong to a single tenant
        return \App\Models\Tenant::where('id', $this->tenant_id)->get();
    }

    /**
     * Check if the user can access the given tenant.
     */
    public function canAccessTenant(EloquentModel $tenant): bool
    {
        return $this->tenant_id === $tenant->id;
    }
}