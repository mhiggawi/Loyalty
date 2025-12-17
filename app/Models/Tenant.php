<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Models\Contracts\HasName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class Tenant extends Model implements \Filament\Models\Contracts\HasCurrentTenantLabel, HasName
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_name',
        'business_slug',
        'business_type',
        'email',
        'phone',
        'logo_url',
        'primary_color',
        'subscription_plan',
        'subscription_status',
        'subscription_expires_at',
        'max_customers',
        'max_staff',
        'trial_ends_at',
        'api_key',
    ];

    protected $casts = [
        'subscription_expires_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'max_customers' => 'integer',
        'max_staff' => 'integer',
    ];

    protected $hidden = [
        'api_key',
    ];

    /**
     * Get the points settings for this tenant.
     */
    public function pointsSettings(): HasOne
    {
        return $this->hasOne(PointsSetting::class);
    }

    /**
     * Get all customer memberships for this tenant.
     */
    public function customerMemberships(): HasMany
    {
        return $this->hasMany(CustomerMembership::class);
    }

    /**
     * Get all tiers for this tenant.
     */
    public function tiers(): HasMany
    {
        return $this->hasMany(Tier::class);
    }

    /**
     * Get all rewards for this tenant.
     */
    public function rewards(): HasMany
    {
        return $this->hasMany(Reward::class);
    }

    /**
     * Get all staff members for this tenant.
     */
    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class);
    }

    /**
     * Get all transactions for this tenant.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get all redemptions for this tenant.
     */
    public function redemptions(): HasMany
    {
        return $this->hasMany(Redemption::class);
    }

    /**
     * Get all notifications for this tenant.
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Check if tenant subscription is active.
     */
    public function isSubscriptionActive(): bool
    {
        return $this->subscription_status === 'active' &&
               ($this->subscription_expires_at === null || $this->subscription_expires_at->isFuture());
    }

    /**
     * Check if tenant is in trial period.
     */
    public function isInTrial(): bool
    {
        return $this->subscription_status === 'trial' &&
               ($this->trial_ends_at === null || $this->trial_ends_at->isFuture());
    }

    /**
     * Check if tenant can add more customers.
     */
    public function canAddCustomers(): bool
    {
        return $this->customerMemberships()->count() < $this->max_customers;
    }

    /**
     * Check if tenant can add more staff.
     */
    public function canAddStaff(): bool
    {
        return $this->staff()->count() < $this->max_staff;
    }

    /**
     * Get subdomain URL.
     */
    public function getSubdomainUrlAttribute(): string
    {
        return "https://{$this->business_slug}." . config('app.domain');
    }

    /**
     * Scope: Active tenants only.
     */
    public function scopeActive($query)
    {
        return $query->whereIn('subscription_status', ['trial', 'active']);
    }

    /**
     * Scope: Tenants with expired subscriptions.
     */
    public function scopeExpired($query)
    {
        return $query->where('subscription_status', 'active')
                     ->where('subscription_expires_at', '<', now());
    }

    /**
     * Get the column name for the tenant identifier (slug instead of ID).
     */
    public function getRouteKeyName(): string
    {
        return 'business_slug';
    }

    /**
     * Get the current tenant label for Filament.
     */
    public function getCurrentTenantLabel(): string
    {
        return $this->business_name ?? 'Unknown Tenant';
    }

    /**
     * Get the tenant's name for Filament.
     */
    public function getFilamentName(): string
    {
        return $this->business_name ?? 'Unknown Tenant';
    }
}
