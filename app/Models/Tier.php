<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tier extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'level',
        'min_points',
        'points_multiplier',
        'benefits',
        'icon',
        'color',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'min_points' => 'integer',
        'points_multiplier' => 'decimal:2',
        'display_order' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the tenant that owns this tier.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Calculate points with tier multiplier.
     */
    public function applyMultiplier(int $basePoints): int
    {
        return (int) ($basePoints * $this->points_multiplier);
    }

    /**
     * Get tier icon (fallback to default if not set).
     */
    public function getIconAttribute($value): string
    {
        if ($value) {
            return $value;
        }

        // Default icons based on level
        return match ($this->level) {
            'bronze' => '🥉',
            'silver' => '🥈',
            'gold' => '🥇',
            'platinum' => '💎',
            default => '⭐',
        };
    }

    /**
     * Get tier color (fallback to default if not set).
     */
    public function getColorAttribute($value): string
    {
        if ($value) {
            return $value;
        }

        // Default colors based on level
        return match ($this->level) {
            'bronze' => '#CD7F32',
            'silver' => '#C0C0C0',
            'gold' => '#FFD700',
            'platinum' => '#E5E4E2',
            default => '#667eea',
        };
    }

    /**
     * Scope: Active tiers only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Tiers for tenant.
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope: Order by min_points.
     */
    public function scopeOrderedByPoints($query, string $direction = 'asc')
    {
        return $query->orderBy('min_points', $direction);
    }
}
