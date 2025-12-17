<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SendGrid Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for SendGrid email service.
    | Get your API key from https://app.sendgrid.com
    |
    */

    'api_key' => env('SENDGRID_API_KEY', ''),

    'from' => [
        'email' => env('SENDGRID_FROM_EMAIL', 'noreply@loyalty-system.com'),
        'name' => env('SENDGRID_FROM_NAME', 'Loyalty System'),
    ],

    /*
    |--------------------------------------------------------------------------
    | SendGrid Templates
    |--------------------------------------------------------------------------
    |
    | Dynamic template IDs from SendGrid.
    | Create templates in SendGrid Dashboard.
    |
    */

    'templates' => [
        'welcome' => env('SENDGRID_TEMPLATE_WELCOME', ''),
        'tier_upgrade' => env('SENDGRID_TEMPLATE_TIER_UPGRADE', ''),
        'reward_redeemed' => env('SENDGRID_TEMPLATE_REWARD_REDEEMED', ''),
        'points_expiring' => env('SENDGRID_TEMPLATE_POINTS_EXPIRING', ''),
        'monthly_summary' => env('SENDGRID_TEMPLATE_MONTHLY_SUMMARY', ''),
    ],

];
