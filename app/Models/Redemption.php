<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Redemption extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'customer_membership_id',
        'reward_id',
        'points_used',
        'status',
        'redemption_code',
        'qr_code_hash',
        'redeemed_at',
        'approved_at',
        'used_at',
        'expires_at',
        'approved_by',
        'used_by',
        'notes',
    ];

    protected $casts = [
        'points_used' => 'integer',
        'redeemed_at' => 'datetime',
        'approved_at' => 'datetime',
        'used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Boot method - auto-generate redemption code.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($redemption) {
            if (!$redemption->redemption_code) {
                $redemption->redemption_code = 'RDM-' . Str::upper(Str::random(6));
            }
            if (!$redemption->qr_code_hash) {
                $redemption->qr_code_hash = 'QR-RED-' . Str::random(20);
            }
            if (!$redemption->redeemed_at) {
                $redemption->redeemed_at = now();
            }
        });
    }

    /**
     * Get the tenant.
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
     * Get the reward.
     */
    public function reward(): BelongsTo
    {
        return $this->belongsTo(Reward::class);
    }

    /**
     * Get the staff who approved.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'approved_by');
    }

    /**
     * Get the staff who marked as used.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'used_by');
    }

    /**
     * Approve redemption.
     */
    public function approve(int $staffId): void
    {
        $this->status = 'approved';
        $this->approved_at = now();
        $this->approved_by = $staffId;
        $this->save();

        // Create notification
        Notification::create([
            'tenant_id' => $this->tenant_id,
            'customer_membership_id' => $this->customer_membership_id,
            'type' => 'redemption_approved',
            'title_ar' => 'تم الموافقة على الاستبدال',
            'title_en' => 'Redemption Approved',
            'message_ar' => 'تم الموافقة على طلب استبدالك',
            'message_en' => 'Your redemption request has been approved',
            'sent_push' => true,
            'sent_at' => now(),
        ]);
    }

    /**
     * Reject redemption.
     */
    public function reject(int $staffId, string $reason = null): void
    {
        $this->status = 'rejected';
        $this->notes = $reason;
        $this->approved_by = $staffId;
        $this->save();

        // Refund points
        $this->customerMembership->increment('current_points', $this->points_used);

        // Create transaction for refund
        Transaction::create([
            'tenant_id' => $this->tenant_id,
            'customer_membership_id' => $this->customer_membership_id,
            'type' => 'adjustment',
            'points' => $this->points_used,
            'description' => 'Redemption rejected - Points refunded',
            'reference_id' => $this->id,
            'reference_type' => 'redemption',
            'staff_id' => $staffId,
            'balance_after' => $this->customerMembership->current_points,
        ]);

        // Create notification
        Notification::create([
            'tenant_id' => $this->tenant_id,
            'customer_membership_id' => $this->customer_membership_id,
            'type' => 'redemption_rejected',
            'title_ar' => 'تم رفض الاستبدال',
            'title_en' => 'Redemption Rejected',
            'message_ar' => $reason ?? 'تم رفض طلب استبدالك',
            'message_en' => $reason ?? 'Your redemption request has been rejected',
            'sent_push' => true,
            'sent_at' => now(),
        ]);
    }

    /**
     * Mark as used.
     */
    public function markAsUsed(int $staffId): void
    {
        $this->status = 'used';
        $this->used_at = now();
        $this->used_by = $staffId;
        $this->save();
    }

    /**
     * Check if redemption is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if redemption is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if redemption is used.
     */
    public function isUsed(): bool
    {
        return $this->status === 'used';
    }

    /**
     * Check if redemption is expired.
     */
    public function isExpired(): bool
    {
        return $this->status === 'expired' ||
               ($this->expires_at && now()->gt($this->expires_at));
    }

    /**
     * Get status label in Arabic.
     */
    public function getStatusLabelAr(): string
    {
        return match ($this->status) {
            'pending' => 'قيد الانتظار',
            'approved' => 'تمت الموافقة',
            'rejected' => 'مرفوض',
            'used' => 'مستخدم',
            'expired' => 'منتهي',
            'cancelled' => 'ملغي',
            default => 'غير معروف',
        };
    }

    /**
     * Get status label in English.
     */
    public function getStatusLabelEn(): string
    {
        return match ($this->status) {
            'pending' => 'Pending',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'used' => 'Used',
            'expired' => 'Expired',
            'cancelled' => 'Cancelled',
            default => 'Unknown',
        };
    }

    /**
     * Scope: Redemptions for tenant.
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope: Redemptions by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Pending redemptions.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: Approved redemptions.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }
}
