<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointsSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'currency_to_points_ratio',
        'points_expiry_months',
        'allow_partial_redemption',
        'min_points_for_redemption',
        'welcome_bonus_points',
        'birthday_bonus_points',
        'referrer_bonus_points',
        'referee_bonus_points',
    ];

    protected $casts = [
        'currency_to_points_ratio' => 'decimal:2',
        'points_expiry_months' => 'integer',
        'allow_partial_redemption' => 'boolean',
        'min_points_for_redemption' => 'integer',
        'welcome_bonus_points' => 'integer',
        'birthday_bonus_points' => 'integer',
        'referrer_bonus_points' => 'integer',
        'referee_bonus_points' => 'integer',
    ];

    /**
     * Get the tenant that owns these settings.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Calculate points from currency amount.
     */
    public function calculatePoints(float $amount): int
    {
        return (int) ($amount * $this->currency_to_points_ratio);
    }

    /**
     * Calculate currency value from points.
     */
    public function calculateCurrency(int $points): float
    {
        if ($this->currency_to_points_ratio <= 0) {
            return 0;
        }

        return round($points / $this->currency_to_points_ratio, 2);
    }

    /**
     * Check if points expire.
     */
    public function doPointsExpire(): bool
    {
        return $this->points_expiry_months !== null;
    }

    /**
     * Get points expiry date from given date.
     */
    public function getExpiryDate(\DateTime $fromDate = null): ?\DateTime
    {
        if (!$this->doPointsExpire()) {
            return null;
        }

        $fromDate = $fromDate ?? new \DateTime();
        return (clone $fromDate)->modify("+{$this->points_expiry_months} months");
    }
}
