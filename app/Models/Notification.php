<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'customer_membership_id',
        'type',
        'title_ar',
        'title_en',
        'message_ar',
        'message_en',
        'action_data',
        'is_read',
        'sent_push',
        'sent_email',
        'sent_sms',
        'priority',
        'scheduled_at',
        'sent_at',
    ];

    protected $casts = [
        'action_data' => 'array',
        'is_read' => 'boolean',
        'sent_push' => 'boolean',
        'sent_email' => 'boolean',
        'sent_sms' => 'boolean',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

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
     * Get title in specified language.
     */
    public function getTitle(string $lang = 'ar'): string
    {
        return $lang === 'ar' ? $this->title_ar : ($this->title_en ?? $this->title_ar);
    }

    /**
     * Get message in specified language.
     */
    public function getMessage(string $lang = 'ar'): string
    {
        return $lang === 'ar' ? $this->message_ar : ($this->message_en ?? $this->message_ar);
    }

    /**
     * Mark notification as read.
     */
    public function markAsRead(): void
    {
        $this->is_read = true;
        $this->save();
    }

    /**
     * Check if notification is read.
     */
    public function isRead(): bool
    {
        return $this->is_read === true;
    }

    /**
     * Check if notification is scheduled.
     */
    public function isScheduled(): bool
    {
        return $this->scheduled_at && $this->scheduled_at->isFuture();
    }

    /**
     * Check if notification was sent.
     */
    public function wasSent(): bool
    {
        return $this->sent_at !== null;
    }

    /**
     * Get type label in Arabic.
     */
    public function getTypeLabelAr(): string
    {
        return match ($this->type) {
            'points_earned' => 'كسب نقاط',
            'tier_upgrade' => 'ترقية المستوى',
            'reward_available' => 'مكافأة متاحة',
            'points_expiring' => 'نقاط على وشك الانتهاء',
            'redemption_approved' => 'تمت الموافقة على الاستبدال',
            'redemption_rejected' => 'رفض الاستبدال',
            'birthday' => 'عيد ميلاد',
            'anniversary' => 'ذكرى سنوية',
            'custom' => 'إشعار مخصص',
            default => 'إشعار',
        };
    }

    /**
     * Get type label in English.
     */
    public function getTypeLabelEn(): string
    {
        return match ($this->type) {
            'points_earned' => 'Points Earned',
            'tier_upgrade' => 'Tier Upgrade',
            'reward_available' => 'Reward Available',
            'points_expiring' => 'Points Expiring',
            'redemption_approved' => 'Redemption Approved',
            'redemption_rejected' => 'Redemption Rejected',
            'birthday' => 'Birthday',
            'anniversary' => 'Anniversary',
            'custom' => 'Custom Notification',
            default => 'Notification',
        };
    }

    /**
     * Scope: Unread notifications.
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope: Sent notifications.
     */
    public function scopeSent($query)
    {
        return $query->whereNotNull('sent_at');
    }

    /**
     * Scope: Scheduled notifications.
     */
    public function scopeScheduled($query)
    {
        return $query->whereNotNull('scheduled_at')
                     ->whereNull('sent_at');
    }

    /**
     * Scope: Notifications for tenant.
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope: Notifications for customer.
     */
    public function scopeForCustomer($query, int $customerMembershipId)
    {
        return $query->where('customer_membership_id', $customerMembershipId);
    }

    /**
     * Scope: Notifications by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: High priority notifications.
     */
    public function scopeHighPriority($query)
    {
        return $query->where('priority', 'high');
    }
}
