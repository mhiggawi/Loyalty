<?php

/**
 * Add these guards to your config/auth.php file
 *
 * Merge this into the 'guards' array:
 */

return [
    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        // Super Admin Guard
        'admin' => [
            'driver' => 'session',
            'provider' => 'admins',
        ],

        // Merchant Staff Guard
        'staff' => [
            'driver' => 'session',
            'provider' => 'staff',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],

        // Super Admin Provider
        'admins' => [
            'driver' => 'eloquent',
            'model' => App\Models\Admin::class,
        ],

        // Merchant Staff Provider
        'staff' => [
            'driver' => 'eloquent',
            'model' => App\Models\Staff::class,
        ],
    ],
];
