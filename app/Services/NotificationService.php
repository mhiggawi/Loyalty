<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\GlobalCustomer;
use App\Models\Tenant;

class NotificationService
{
    protected FirebaseService $firebaseService;

    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    /**
     * Create a notification and optionally send push notification
     */
    public function createNotification(
        int $tenantId,
        int $customerId,
        string $type,
        string $titleEn,
        string $messageEn,
        string $titleAr,
        string $messageAr,
        bool $sendPush = true
    ): Notification {
        // Create database notification
        $notification = Notification::create([
            'tenant_id' => $tenantId,
            'global_customer_id' => $customerId,
            'type' => $type,
            'title_en' => $titleEn,
            'message_en' => $messageEn,
            'title_ar' => $titleAr,
            'message_ar' => $messageAr,
            'is_read' => false,
        ]);

        // Send push notification if enabled
        if ($sendPush) {
            $this->sendPushNotification($notification);
        }

        return $notification;
    }

    /**
     * Send push notification via FCM
     */
    protected function sendPushNotification(Notification $notification): void
    {
        $customer = GlobalCustomer::find($notification->global_customer_id);

        if (!$customer || !$customer->device_token) {
            return; // No device token, skip push notification
        }

        $language = $customer->language ?? 'en';
        $title = $language === 'ar' ? $notification->title_ar : $notification->title_en;
        $message = $language === 'ar' ? $notification->message_ar : $notification->message_en;

        $this->firebaseService->sendToDevice(
            $customer->device_token,
            [
                'title' => $title,
                'body' => $message,
            ],
            [
                'type' => $notification->type,
                'notification_id' => $notification->id,
            ]
        );
    }

    /**
     * Notify about points earned
     */
    public function notifyPointsEarned(
        int $tenantId,
        int $customerId,
        int $points,
        string $businessName
    ): Notification {
        return $this->createNotification(
            $tenantId,
            $customerId,
            'points_earned',
            'Points Earned',
            "You've earned {$points} points from {$businessName}",
            'نقاط جديدة',
            "لقد حصلت على {$points} نقطة من {$businessName}"
        );
    }

    /**
     * Notify about tier upgrade
     */
    public function notifyTierUpgrade(
        int $tenantId,
        int $customerId,
        string $newTier,
        string $tierNameEn,
        string $tierNameAr,
        string $businessName
    ): Notification {
        return $this->createNotification(
            $tenantId,
            $customerId,
            'tier_upgrade',
            'Tier Upgrade!',
            "Congratulations! You've been upgraded to {$tierNameEn} at {$businessName}",
            'ترقية المستوى!',
            "تهانينا! لقد تمت ترقيتك إلى {$tierNameAr} في {$businessName}"
        );
    }

    /**
     * Notify about reward redemption
     */
    public function notifyRewardRedeemed(
        int $tenantId,
        int $customerId,
        string $rewardTitleEn,
        string $rewardTitleAr,
        string $redemptionCode
    ): Notification {
        return $this->createNotification(
            $tenantId,
            $customerId,
            'reward_redeemed',
            'Reward Redeemed',
            "You've redeemed: {$rewardTitleEn}. Code: {$redemptionCode}",
            'تم استبدال المكافأة',
            "لقد قمت باستبدال: {$rewardTitleAr}. الكود: {$redemptionCode}"
        );
    }

    /**
     * Notify about welcome bonus
     */
    public function notifyWelcomeBonus(
        int $tenantId,
        int $customerId,
        int $bonusPoints,
        string $businessName
    ): Notification {
        return $this->createNotification(
            $tenantId,
            $customerId,
            'welcome_bonus',
            'Welcome Bonus',
            "Welcome! You've received {$bonusPoints} bonus points from {$businessName}",
            'مكافأة الترحيب',
            "مرحباً! لقد حصلت على {$bonusPoints} نقطة مكافأة من {$businessName}"
        );
    }

    /**
     * Notify about new reward available
     */
    public function notifyNewReward(
        int $tenantId,
        int $customerId,
        string $rewardTitleEn,
        string $rewardTitleAr,
        int $pointsRequired
    ): Notification {
        return $this->createNotification(
            $tenantId,
            $customerId,
            'reward_available',
            'New Reward Available',
            "{$rewardTitleEn} is now available for {$pointsRequired} points!",
            'مكافأة جديدة متاحة',
            "{$rewardTitleAr} متاح الآن مقابل {$pointsRequired} نقطة!"
        );
    }

    /**
     * Notify about points expiring soon
     */
    public function notifyPointsExpiring(
        int $tenantId,
        int $customerId,
        int $points,
        string $expiryDate,
        string $businessName
    ): Notification {
        return $this->createNotification(
            $tenantId,
            $customerId,
            'points_expiring',
            'Points Expiring Soon',
            "{$points} points will expire on {$expiryDate} at {$businessName}. Use them now!",
            'النقاط ستنتهي قريباً',
            "{$points} نقطة ستنتهي في {$expiryDate} في {$businessName}. استخدمها الآن!"
        );
    }

    /**
     * Notify about birthday bonus
     */
    public function notifyBirthdayBonus(
        int $tenantId,
        int $customerId,
        int $bonusPoints,
        string $businessName
    ): Notification {
        return $this->createNotification(
            $tenantId,
            $customerId,
            'birthday_bonus',
            'Happy Birthday!',
            "Happy Birthday! You've received {$bonusPoints} bonus points from {$businessName}",
            'عيد ميلاد سعيد!',
            "عيد ميلاد سعيد! لقد حصلت على {$bonusPoints} نقطة مكافأة من {$businessName}"
        );
    }

    /**
     * Notify about referral bonus
     */
    public function notifyReferralBonus(
        int $tenantId,
        int $customerId,
        int $bonusPoints,
        string $referredCustomerName
    ): Notification {
        return $this->createNotification(
            $tenantId,
            $customerId,
            'referral_bonus',
            'Referral Bonus',
            "You've earned {$bonusPoints} points for referring {$referredCustomerName}!",
            'مكافأة الإحالة',
            "لقد حصلت على {$bonusPoints} نقطة لإحالة {$referredCustomerName}!"
        );
    }
}
