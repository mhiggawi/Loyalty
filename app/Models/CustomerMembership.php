<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class CustomerMembership extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'global_customer_id',
        'tenant_id',
        'current_points',
        'total_points_earned',
        'total_points_redeemed',
        'total_visits',
        'total_spent',
        'tier_level',
        'tier_upgraded_at',
        'membership_status',
        'qr_code_hash',
        'joined_at',
        'last_visit_at',
    ];

    protected $casts = [
        'current_points' => 'integer',
        'total_points_earned' => 'integer',
        'total_points_redeemed' => 'integer',
        'total_visits' => 'integer',
        'total_spent' => 'decimal:2',
        'tier_upgraded_at' => 'datetime',
        'joined_at' => 'datetime',
        'last_visit_at' => 'datetime',
    ];

    /**
     * Boot method - auto-generate QR code hash.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($membership) {
            if (!$membership->qr_code_hash) {
                $membership->qr_code_hash = 'QR-' . Str::random(20);
            }
            if (!$membership->joined_at) {
                $membership->joined_at = now();
            }
        });
    }

    /**
     * Get the customer that owns this membership.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(GlobalCustomer::class, 'global_customer_id');
    }

    /**
     * Get the tenant (merchant) this membership belongs to.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get all transactions for this membership.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get all redemptions for this membership.
     */
    public function redemptions(): HasMany
    {
        return $this->hasMany(Redemption::class);
    }

    /**
     * Get all notifications for this membership.
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Get the current tier details.
     */
    public function currentTier(): ?Tier
    {
        return Tier::where('tenant_id', $this->tenant_id)
                   ->where('level', $this->tier_level)
                   ->first();
    }

    /**
     * Get the next tier.
     */
    public function nextTier(): ?Tier
    {
        $currentTier = $this->currentTier();
        if (!$currentTier) {
            return null;
        }

        return Tier::where('tenant_id', $this->tenant_id)
                   ->where('min_points', '>', $currentTier->min_points)
                   ->orderBy('min_points', 'asc')
                   ->first();
    }

    /**
     * Calculate points needed for next tier.
     */
    public function pointsNeededForNextTier(): int
    {
        $nextTier = $this->nextTier();
        if (!$nextTier) {
            return 0; // Already at max tier
        }

        return max(0, $nextTier->min_points - $this->current_points);
    }

    /**
     * Check if customer qualifies for tier upgrade.
     */
    public function checkAndUpgradeTier(): bool
    {
        $currentTier = $this->currentTier();
        if (!$currentTier) {
            return false;
        }

        // Get all tiers for this tenant ordered by min_points
        $tiers = Tier::where('tenant_id', $this->tenant_id)
                     ->where('is_active', true)
                     ->orderBy('min_points', 'desc')
                     ->get();

        // Find the highest tier customer qualifies for
        foreach ($tiers as $tier) {
            if ($this->current_points >= $tier->min_points && $tier->level !== $this->tier_level) {
                $this->tier_level = $tier->level;
                $this->tier_upgraded_at = now();
                $this->save();

                // Create tier upgrade notification
                Notification::create([
                    'tenant_id' => $this->tenant_id,
                    'customer_membership_id' => $this->id,
                    'type' => 'tier_upgrade',
                    'title_ar' => 'ترقية المستوى!',
                    'title_en' => 'Tier Upgrade!',
                    'message_ar' => "مبروك! أصبحت الآن من فئة {$tier->name}",
                    'message_en' => "Congratulations! You are now {$tier->name} tier",
                    'sent_push' => true,
                    'sent_at' => now(),
                ]);

                return true;
            }
        }

        return false;
    }

    /**
     * Check if membership is active.
     */
    public function isActive(): bool
    {
        return $this->membership_status === 'active';
    }

    /**
     * Get progress to next tier (0-100%).
     */
    public function getTierProgressAttribute(): int
    {
        $currentTier = $this->currentTier();
        $nextTier = $this->nextTier();

        if (!$currentTier || !$nextTier) {
            return 100; // Already at max tier
        }

        $pointsInCurrentRange = $this->current_points - $currentTier->min_points;
        $totalPointsNeeded = $nextTier->min_points - $currentTier->min_points;

        if ($totalPointsNeeded <= 0) {
            return 100;
        }

        return (int) min(100, ($pointsInCurrentRange / $totalPointsNeeded) * 100);
    }

    /**
     * Get days since last visit.
     */
    public function getDaysSinceLastVisitAttribute(): ?int
    {
        return $this->last_visit_at?->diffInDays(now());
    }

    /**
     * Get membership duration in days.
     */
    public function getMembershipDurationAttribute(): int
    {
        return $this->joined_at->diffInDays(now());
    }

    /**
     * Scope: Active memberships only.
     */
    public function scopeActive($query)
    {
        return $query->where('membership_status', 'active');
    }

    /**
     * Scope: Memberships by tenant.
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope: Memberships by tier.
     */
    public function scopeByTier($query, string $tierLevel)
    {
        return $query->where('tier_level', $tierLevel);
    }

    /**
     * Scope: Inactive customers (not visited in X days).
     */
    public function scopeInactive($query, int $days = 30)
    {
        return $query->where('last_visit_at', '<', now()->subDays($days))
                     ->orWhereNull('last_visit_at');
    }
}
