<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Firebase Cloud Messaging Configuration
    |--------------------------------------------------------------------------
    |
    | Firebase configuration for push notifications.
    | You need to download the service account JSON from Firebase Console.
    |
    */

    'credentials' => [
        'file' => env('FIREBASE_CREDENTIALS', storage_path('app/firebase-credentials.json')),
    ],

    'project_id' => env('FIREBASE_PROJECT_ID', ''),

    'database_url' => env('FIREBASE_DATABASE_URL', ''),

    /*
    |--------------------------------------------------------------------------
    | FCM API Key (Legacy)
    |--------------------------------------------------------------------------
    |
    | Server key from Firebase Console for FCM API.
    | New projects should use service account credentials instead.
    |
    */

    'server_key' => env('FIREBASE_SERVER_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | FCM Endpoint
    |--------------------------------------------------------------------------
    */

    'fcm_endpoint' => 'https://fcm.googleapis.com/fcm/send',

];
