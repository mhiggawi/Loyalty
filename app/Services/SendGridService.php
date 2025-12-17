<?php

namespace App\Services;

use SendGrid;
use SendGrid\Mail\Mail;
use Illuminate\Support\Facades\Log;

class SendGridService
{
    protected SendGrid $client;
    protected string $fromEmail;
    protected string $fromName;

    public function __construct()
    {
        $apiKey = config('sendgrid.api_key');
        $this->client = new SendGrid($apiKey);
        $this->fromEmail = config('sendgrid.from.email');
        $this->fromName = config('sendgrid.from.name');
    }

    /**
     * Send email using SendGrid
     */
    public function sendEmail(string $toEmail, string $subject, string $htmlContent, ?string $plainContent = null): bool
    {
        try {
            $email = new Mail();
            $email->setFrom($this->fromEmail, $this->fromName);
            $email->setSubject($subject);
            $email->addTo($toEmail);
            $email->addContent('text/html', $htmlContent);

            if ($plainContent) {
                $email->addContent('text/plain', $plainContent);
            }

            $response = $this->client->send($email);

            if ($response->statusCode() >= 200 && $response->statusCode() < 300) {
                Log::info('Email sent successfully via SendGrid', [
                    'to' => $toEmail,
                    'subject' => $subject,
                ]);
                return true;
            }

            Log::error('SendGrid email failed', [
                'status_code' => $response->statusCode(),
                'body' => $response->body(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('SendGrid email exception', [
                'to' => $toEmail,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send email using dynamic template
     */
    public function sendTemplateEmail(string $toEmail, string $templateId, array $dynamicData): bool
    {
        try {
            $email = new Mail();
            $email->setFrom($this->fromEmail, $this->fromName);
            $email->addTo($toEmail);
            $email->setTemplateId($templateId);
            $email->addDynamicTemplateDatas($dynamicData);

            $response = $this->client->send($email);

            if ($response->statusCode() >= 200 && $response->statusCode() < 300) {
                Log::info('Template email sent successfully via SendGrid', [
                    'to' => $toEmail,
                    'template' => $templateId,
                ]);
                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('SendGrid template email exception', [
                'to' => $toEmail,
                'template' => $templateId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send welcome email
     */
    public function sendWelcomeEmail(string $toEmail, string $customerName, string $businessName, int $bonusPoints): bool
    {
        $subject = "Welcome to {$businessName}!";

        $html = "
            <h1>Welcome, {$customerName}!</h1>
            <p>Thank you for joining {$businessName}'s loyalty program.</p>
            <p>You've received <strong>{$bonusPoints} bonus points</strong> to get you started!</p>
            <p>Start earning rewards with every purchase.</p>
            <br>
            <p>Best regards,<br>{$businessName}</p>
        ";

        $plain = "Welcome, {$customerName}! Thank you for joining {$businessName}'s loyalty program. You've received {$bonusPoints} bonus points to get you started!";

        return $this->sendEmail($toEmail, $subject, $html, $plain);
    }

    /**
     * Send tier upgrade email
     */
    public function sendTierUpgradeEmail(string $toEmail, string $customerName, string $newTier, string $businessName): bool
    {
        $subject = "Congratulations! Tier Upgrade at {$businessName}";

        $html = "
            <h1>Congratulations, {$customerName}!</h1>
            <p>You've been upgraded to <strong>{$newTier}</strong> tier at {$businessName}!</p>
            <p>Enjoy exclusive benefits and higher point multipliers.</p>
            <br>
            <p>Best regards,<br>{$businessName}</p>
        ";

        $plain = "Congratulations, {$customerName}! You've been upgraded to {$newTier} tier at {$businessName}!";

        return $this->sendEmail($toEmail, $subject, $html, $plain);
    }

    /**
     * Send reward redemption confirmation email
     */
    public function sendRedemptionConfirmation(string $toEmail, string $customerName, string $rewardTitle, string $redemptionCode): bool
    {
        $subject = "Reward Redeemed Successfully";

        $html = "
            <h1>Reward Redeemed!</h1>
            <p>Hi {$customerName},</p>
            <p>You've successfully redeemed: <strong>{$rewardTitle}</strong></p>
            <p>Your redemption code: <strong style='font-size: 24px; color: #8B5CF6;'>{$redemptionCode}</strong></p>
            <p>Show this code to the staff to claim your reward.</p>
            <br>
            <p>Enjoy your reward!</p>
        ";

        $plain = "Hi {$customerName}, You've successfully redeemed: {$rewardTitle}. Your redemption code: {$redemptionCode}. Show this code to the staff.";

        return $this->sendEmail($toEmail, $subject, $html, $plain);
    }

    /**
     * Send points expiry reminder email
     */
    public function sendPointsExpiryReminder(string $toEmail, string $customerName, int $points, string $expiryDate, string $businessName): bool
    {
        $subject = "Points Expiring Soon at {$businessName}";

        $html = "
            <h1>Points Expiring Soon!</h1>
            <p>Hi {$customerName},</p>
            <p><strong>{$points} points</strong> will expire on <strong>{$expiryDate}</strong>.</p>
            <p>Don't miss out! Use your points now to redeem rewards.</p>
            <br>
            <p>Best regards,<br>{$businessName}</p>
        ";

        $plain = "Hi {$customerName}, {$points} points will expire on {$expiryDate}. Use your points now!";

        return $this->sendEmail($toEmail, $subject, $html, $plain);
    }

    /**
     * Send monthly points summary email
     */
    public function sendMonthlySummary(string $toEmail, string $customerName, int $pointsEarned, int $pointsRedeemed, int $currentBalance, string $businessName): bool
    {
        $subject = "Your Monthly Points Summary - {$businessName}";

        $html = "
            <h1>Monthly Points Summary</h1>
            <p>Hi {$customerName},</p>
            <p>Here's your points activity for the month:</p>
            <ul>
                <li>Points Earned: <strong>+{$pointsEarned}</strong></li>
                <li>Points Redeemed: <strong>-{$pointsRedeemed}</strong></li>
                <li>Current Balance: <strong>{$currentBalance} points</strong></li>
            </ul>
            <p>Keep earning to unlock more rewards!</p>
            <br>
            <p>Best regards,<br>{$businessName}</p>
        ";

        $plain = "Hi {$customerName}, Monthly Summary: Earned: +{$pointsEarned}, Redeemed: -{$pointsRedeemed}, Balance: {$currentBalance} points.";

        return $this->sendEmail($toEmail, $subject, $html, $plain);
    }

    /**
     * Send password reset email (if using email/password auth)
     */
    public function sendPasswordResetEmail(string $toEmail, string $customerName, string $resetUrl): bool
    {
        $subject = "Reset Your Password";

        $html = "
            <h1>Password Reset Request</h1>
            <p>Hi {$customerName},</p>
            <p>We received a request to reset your password.</p>
            <p><a href='{$resetUrl}' style='background-color: #8B5CF6; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; display: inline-block;'>Reset Password</a></p>
            <p>If you didn't request this, please ignore this email.</p>
            <p>This link will expire in 60 minutes.</p>
        ";

        $plain = "Hi {$customerName}, Click this link to reset your password: {$resetUrl}. Link expires in 60 minutes.";

        return $this->sendEmail($toEmail, $subject, $html, $plain);
    }
}
