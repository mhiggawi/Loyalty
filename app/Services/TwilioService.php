<?php

namespace App\Services;

use Twilio\Rest\Client;
use Twilio\Exceptions\TwilioException;
use Illuminate\Support\Facades\Log;

class TwilioService
{
    protected Client $client;
    protected string $fromNumber;
    protected ?string $verifySid;

    public function __construct()
    {
        $accountSid = config('twilio.account_sid');
        $authToken = config('twilio.auth_token');

        $this->client = new Client($accountSid, $authToken);
        $this->fromNumber = config('twilio.from');
        $this->verifySid = config('twilio.verify_sid');
    }

    /**
     * Send OTP using Twilio Verify
     */
    public function sendOtp(string $phoneNumber, string $channel = 'sms'): bool
    {
        try {
            if (!$this->verifySid) {
                Log::error('Twilio Verify SID not configured');
                return false;
            }

            $verification = $this->client->verify->v2
                ->services($this->verifySid)
                ->verifications
                ->create($phoneNumber, $channel);

            if ($verification->status === 'pending') {
                Log::info('OTP sent successfully via Twilio', [
                    'phone' => $phoneNumber,
                    'channel' => $channel,
                ]);
                return true;
            }

            return false;
        } catch (TwilioException $e) {
            Log::error('Twilio OTP send failed', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Verify OTP using Twilio Verify
     */
    public function verifyOtp(string $phoneNumber, string $code): bool
    {
        try {
            if (!$this->verifySid) {
                Log::error('Twilio Verify SID not configured');
                return false;
            }

            $verificationCheck = $this->client->verify->v2
                ->services($this->verifySid)
                ->verificationChecks
                ->create([
                    'to' => $phoneNumber,
                    'code' => $code,
                ]);

            if ($verificationCheck->status === 'approved') {
                Log::info('OTP verified successfully via Twilio', [
                    'phone' => $phoneNumber,
                ]);
                return true;
            }

            return false;
        } catch (TwilioException $e) {
            Log::error('Twilio OTP verification failed', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send SMS message (general purpose)
     */
    public function sendSms(string $toNumber, string $message): bool
    {
        try {
            $this->client->messages->create($toNumber, [
                'from' => $this->fromNumber,
                'body' => $message,
            ]);

            Log::info('SMS sent successfully via Twilio', [
                'to' => $toNumber,
            ]);

            return true;
        } catch (TwilioException $e) {
            Log::error('Twilio SMS send failed', [
                'to' => $toNumber,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send confirmation SMS for reward redemption
     */
    public function sendRedemptionConfirmation(string $phoneNumber, string $rewardTitle, string $redemptionCode): bool
    {
        $message = "Your redemption code for '{$rewardTitle}': {$redemptionCode}. Show this code to the staff.";

        return $this->sendSms($phoneNumber, $message);
    }

    /**
     * Send tier upgrade SMS
     */
    public function sendTierUpgradeSms(string $phoneNumber, string $tierName, string $businessName): bool
    {
        $message = "Congratulations! You've been upgraded to {$tierName} tier at {$businessName}!";

        return $this->sendSms($phoneNumber, $message);
    }

    /**
     * Send points expiry reminder SMS
     */
    public function sendPointsExpiryReminder(string $phoneNumber, int $points, string $expiryDate, string $businessName): bool
    {
        $message = "{$points} points will expire on {$expiryDate} at {$businessName}. Use them before they expire!";

        return $this->sendSms($phoneNumber, $message);
    }

    /**
     * Send welcome SMS
     */
    public function sendWelcomeSms(string $phoneNumber, string $businessName, int $bonusPoints): bool
    {
        $message = "Welcome to {$businessName}! You've received {$bonusPoints} bonus points. Start earning rewards today!";

        return $this->sendSms($phoneNumber, $message);
    }
}
