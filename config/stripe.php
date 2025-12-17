<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Stripe API Keys
    |--------------------------------------------------------------------------
    |
    | Your Stripe API keys from the Stripe Dashboard.
    | Use test keys in development and live keys in production.
    |
    */

    'secret' => env('STRIPE_SECRET', ''),

    'public' => env('STRIPE_KEY', ''),

    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | Stripe API Version
    |--------------------------------------------------------------------------
    |
    | The Stripe API version to use for requests.
    |
    */

    'api_version' => '2023-10-16',

    /*
    |--------------------------------------------------------------------------
    | Subscription Plans
    |--------------------------------------------------------------------------
    |
    | Define your subscription plans with Stripe Price IDs.
    | These should match the prices created in your Stripe Dashboard.
    |
    */

    'plans' => [
        'free_trial' => [
            'name' => 'Free Trial',
            'price' => 0,
            'stripe_price_id' => null, // No Stripe price for free trial
            'features' => [
                'max_customers' => 50,
                'max_staff' => 1,
                'max_rewards' => 5,
                'trial_days' => 14,
            ],
        ],

        'starter' => [
            'name' => 'Starter Plan',
            'price' => 29, // USD per month
            'stripe_price_id' => env('STRIPE_STARTER_PRICE_ID', ''),
            'features' => [
                'max_customers' => 500,
                'max_staff' => 3,
                'max_rewards' => 20,
            ],
        ],

        'professional' => [
            'name' => 'Professional Plan',
            'price' => 99, // USD per month
            'stripe_price_id' => env('STRIPE_PROFESSIONAL_PRICE_ID', ''),
            'features' => [
                'max_customers' => 2000,
                'max_staff' => 10,
                'max_rewards' => 50,
            ],
        ],

        'enterprise' => [
            'name' => 'Enterprise Plan',
            'price' => 299, // USD per month
            'stripe_price_id' => env('STRIPE_ENTERPRISE_PRICE_ID', ''),
            'features' => [
                'max_customers' => null, // Unlimited
                'max_staff' => null, // Unlimited
                'max_rewards' => null, // Unlimited
            ],
        ],
    ],

];
