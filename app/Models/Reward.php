<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reward extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'title_ar',
        'title_en',
        'description_ar',
        'description_en',
        'image_url',
        'category',
        'reward_type',
        'discount_value',
        'points_required',
        'stock',
        'is_active',
        'display_order',
        'min_tier_required',
        'terms_ar',
        'terms_en',
        'valid_from',
        'valid_until',
        'total_redemptions',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'points_required' => 'integer',
        'stock' => 'integer',
        'is_active' => 'boolean',
        'display_order' => 'integer',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'total_redemptions' => 'integer',
    ];

    /**
     * Get the tenant that owns this reward.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get all redemptions for this reward.
     */
    public function redemptions(): HasMany
    {
        return $this->hasMany(Redemption::class);
    }

    /**
     * Get title in specified language.
     */
    public function getTitle(string $lang = 'ar'): string
    {
        return $lang === 'ar' ? $this->title_ar : ($this->title_en ?? $this->title_ar);
    }

    /**
     * Get description in specified language.
     */
    public function getDescription(string $lang = 'ar'): ?string
    {
        return $lang === 'ar' ? $this->description_ar : ($this->description_en ?? $this->description_ar);
    }

    /**
     * Check if reward is available for redemption.
     */
    public function isAvailable(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        // Check stock
        if ($this->stock !== null && $this->stock <= 0) {
            return false;
        }

        // Check validity dates
        if ($this->valid_from && now()->lt($this->valid_from)) {
            return false;
        }

        if ($this->valid_until && now()->gt($this->valid_until)) {
            return false;
        }

        return true;
    }

    /**
     * Check if customer's tier qualifies for this reward.
     */
    public function isTierEligible(string $customerTier): bool
    {
        if (!$this->min_tier_required) {
            return true; // No tier requirement
        }

        $tierHierarchy = ['bronze' => 1, 'silver' => 2, 'gold' => 3, 'platinum' => 4];

        return $tierHierarchy[$customerTier] >= $tierHierarchy[$this->min_tier_required];
    }

    /**
     * Check if customer can redeem this reward.
     */
    public function canBeRedeemedBy(CustomerMembership $membership): bool
    {
        if (!$this->isAvailable()) {
            return false;
        }

        // Check points
        if ($membership->current_points < $this->points_required) {
            return false;
        }

        // Check tier
        if (!$this->isTierEligible($membership->tier_level)) {
            return false;
        }

        return true;
    }

    /**
     * Decrease stock.
     */
    public function decreaseStock(int $quantity = 1): void
    {
        if ($this->stock !== null) {
            $this->stock = max(0, $this->stock - $quantity);
            $this->save();
        }
    }

    /**
     * Increment redemption count.
     */
    public function incrementRedemptions(): void
    {
        $this->increment('total_redemptions');
    }

    /**
     * Scope: Active rewards only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Available rewards (active + in stock + valid dates).
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_active', true)
                     ->where(function ($q) {
                         $q->whereNull('stock')
                           ->orWhere('stock', '>', 0);
                     })
                     ->where(function ($q) {
                         $q->whereNull('valid_from')
                           ->orWhere('valid_from', '<=', now());
                     })
                     ->where(function ($q) {
                         $q->whereNull('valid_until')
                           ->orWhere('valid_until', '>=', now());
                     });
    }

    /**
     * Scope: Rewards for tenant.
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope: Rewards by category.
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope: Order by display order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order', 'asc')
                     ->orderBy('points_required', 'asc');
    }
}
