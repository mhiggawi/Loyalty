<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Twilio Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Twilio SMS and Voice services.
    | Get your credentials from https://console.twilio.com
    |
    */

    'account_sid' => env('TWILIO_ACCOUNT_SID', ''),

    'auth_token' => env('TWILIO_AUTH_TOKEN', ''),

    'from' => env('TWILIO_FROM_NUMBER', ''),

    /*
    |--------------------------------------------------------------------------
    | Twilio Verify Service (For OTP)
    |--------------------------------------------------------------------------
    |
    | Twilio Verify provides OTP verification out of the box.
    | Create a Verify Service in Twilio Console.
    |
    */

    'verify_sid' => env('TWILIO_VERIFY_SID', ''),

];
