<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'customer_membership_id',
        'type',
        'points',
        'amount',
        'description',
        'reference_id',
        'reference_type',
        'staff_id',
        'balance_after',
        'metadata',
    ];

    protected $casts = [
        'points' => 'integer',
        'amount' => 'decimal:2',
        'balance_after' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * Get the tenant this transaction belongs to.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the customer membership.
     */
    public function customerMembership(): BelongsTo
    {
        return $this->belongsTo(CustomerMembership::class);
    }

    /**
     * Get the staff member who created this transaction (if any).
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    /**
     * Check if transaction is a credit (adds points).
     */
    public function isCredit(): bool
    {
        return $this->points > 0;
    }

    /**
     * Check if transaction is a debit (removes points).
     */
    public function isDebit(): bool
    {
        return $this->points < 0;
    }

    /**
     * Get transaction type label in Arabic.
     */
    public function getTypeLabelAr(): string
    {
        return match ($this->type) {
            'earn' => 'كسب نقاط',
            'redeem' => 'استبدال نقاط',
            'bonus' => 'نقاط مكافأة',
            'referral' => 'نقاط إحالة',
            'manual_add' => 'إضافة يدوية',
            'manual_subtract' => 'خصم يدوي',
            'expire' => 'انتهاء صلاحية',
            'adjustment' => 'تعديل',
            default => 'معاملة',
        };
    }

    /**
     * Get transaction type label in English.
     */
    public function getTypeLabelEn(): string
    {
        return match ($this->type) {
            'earn' => 'Points Earned',
            'redeem' => 'Points Redeemed',
            'bonus' => 'Bonus Points',
            'referral' => 'Referral Points',
            'manual_add' => 'Manual Addition',
            'manual_subtract' => 'Manual Deduction',
            'expire' => 'Points Expired',
            'adjustment' => 'Adjustment',
            default => 'Transaction',
        };
    }

    /**
     * Scope: Transactions for tenant.
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope: Transactions for customer.
     */
    public function scopeForCustomer($query, int $customerMembershipId)
    {
        return $query->where('customer_membership_id', $customerMembershipId);
    }

    /**
     * Scope: Transactions by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: Credit transactions only.
     */
    public function scopeCredits($query)
    {
        return $query->where('points', '>', 0);
    }

    /**
     * Scope: Debit transactions only.
     */
    public function scopeDebits($query)
    {
        return $query->where('points', '<', 0);
    }

    /**
     * Scope: Transactions within date range.
     */
    public function scopeWithinDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }
}
