<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FirebaseService
{
    protected string $serverKey;
    protected string $fcmEndpoint;

    public function __construct()
    {
        $this->serverKey = config('firebase.server_key');
        $this->fcmEndpoint = config('firebase.fcm_endpoint');
    }

    /**
     * Send push notification to a single device
     */
    public function sendToDevice(string $deviceToken, array $notification, array $data = []): bool
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->serverKey,
                'Content-Type' => 'application/json',
            ])->post($this->fcmEndpoint, [
                'to' => $deviceToken,
                'notification' => $notification,
                'data' => $data,
                'priority' => 'high',
            ]);

            if ($response->successful()) {
                Log::info('FCM notification sent successfully', [
                    'device_token' => substr($deviceToken, 0, 20) . '...',
                ]);
                return true;
            }

            Log::error('FCM notification failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('FCM notification exception', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send push notification to multiple devices
     */
    public function sendToMultipleDevices(array $deviceTokens, array $notification, array $data = []): bool
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->serverKey,
                'Content-Type' => 'application/json',
            ])->post($this->fcmEndpoint, [
                'registration_ids' => $deviceTokens,
                'notification' => $notification,
                'data' => $data,
                'priority' => 'high',
            ]);

            if ($response->successful()) {
                Log::info('FCM notification sent to multiple devices', [
                    'count' => count($deviceTokens),
                ]);
                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('FCM multiple devices notification exception', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send notification for points earned
     */
    public function sendPointsEarnedNotification(string $deviceToken, int $points, string $businessName, string $language = 'en'): bool
    {
        $notification = $language === 'ar' ? [
            'title' => 'نقاط جديدة!',
            'body' => "لقد حصلت على {$points} نقطة من {$businessName}",
        ] : [
            'title' => 'Points Earned!',
            'body' => "You've earned {$points} points from {$businessName}",
        ];

        return $this->sendToDevice($deviceToken, $notification, [
            'type' => 'points_earned',
            'points' => $points,
        ]);
    }

    /**
     * Send notification for tier upgrade
     */
    public function sendTierUpgradeNotification(string $deviceToken, string $newTier, string $businessName, string $language = 'en'): bool
    {
        $notification = $language === 'ar' ? [
            'title' => 'تهانينا! ترقية المستوى',
            'body' => "لقد تمت ترقيتك إلى مستوى {$newTier} في {$businessName}",
        ] : [
            'title' => 'Congratulations! Tier Upgrade',
            'body' => "You've been upgraded to {$newTier} tier at {$businessName}",
        ];

        return $this->sendToDevice($deviceToken, $notification, [
            'type' => 'tier_upgrade',
            'tier' => $newTier,
        ]);
    }

    /**
     * Send notification for reward redemption
     */
    public function sendRewardRedeemedNotification(string $deviceToken, string $rewardTitle, string $redemptionCode, string $language = 'en'): bool
    {
        $notification = $language === 'ar' ? [
            'title' => 'تم استبدال المكافأة',
            'body' => "تم استبدال: {$rewardTitle}. الكود: {$redemptionCode}",
        ] : [
            'title' => 'Reward Redeemed',
            'body' => "Redeemed: {$rewardTitle}. Code: {$redemptionCode}",
        ];

        return $this->sendToDevice($deviceToken, $notification, [
            'type' => 'reward_redeemed',
            'redemption_code' => $redemptionCode,
        ]);
    }

    /**
     * Send notification for points expiring soon
     */
    public function sendPointsExpiringNotification(string $deviceToken, int $points, string $expiryDate, string $businessName, string $language = 'en'): bool
    {
        $notification = $language === 'ar' ? [
            'title' => 'النقاط ستنتهي قريباً',
            'body' => "{$points} نقطة ستنتهي في {$expiryDate} - استخدمها الآن!",
        ] : [
            'title' => 'Points Expiring Soon',
            'body' => "{$points} points will expire on {$expiryDate} - use them now!",
        ];

        return $this->sendToDevice($deviceToken, $notification, [
            'type' => 'points_expiring',
            'points' => $points,
            'expiry_date' => $expiryDate,
        ]);
    }

    /**
     * Send welcome notification
     */
    public function sendWelcomeNotification(string $deviceToken, string $businessName, int $bonusPoints, string $language = 'en'): bool
    {
        $notification = $language === 'ar' ? [
            'title' => 'مرحباً بك!',
            'body' => "شكراً للانضمام إلى {$businessName}! حصلت على {$bonusPoints} نقطة ترحيبية.",
        ] : [
            'title' => 'Welcome!',
            'body' => "Thanks for joining {$businessName}! You've received {$bonusPoints} welcome points.",
        ];

        return $this->sendToDevice($deviceToken, $notification, [
            'type' => 'welcome_bonus',
            'bonus_points' => $bonusPoints,
        ]);
    }

    /**
     * Send notification for new reward available
     */
    public function sendNewRewardNotification(string $deviceToken, string $rewardTitle, int $pointsRequired, string $language = 'en'): bool
    {
        $notification = $language === 'ar' ? [
            'title' => 'مكافأة جديدة متاحة!',
            'body' => "{$rewardTitle} - {$pointsRequired} نقطة",
        ] : [
            'title' => 'New Reward Available!',
            'body' => "{$rewardTitle} - {$pointsRequired} points",
        ];

        return $this->sendToDevice($deviceToken, $notification, [
            'type' => 'reward_available',
            'points_required' => $pointsRequired,
        ]);
    }
}
