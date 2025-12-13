<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Firebase Service Account
    |--------------------------------------------------------------------------
    |
    | Path to the Firebase service account JSON file.
    |
    */
    'credentials' => env('FIREBASE_CREDENTIALS', storage_path('app/dion-31580-firebase-adminsdk-fbsvc-3760176ec2.json')),

    /*
    |--------------------------------------------------------------------------
    | Firebase Project ID
    |--------------------------------------------------------------------------
    |
    | Your Firebase project ID.
    |
    */
    'project_id' => env('FIREBASE_PROJECT_ID', 'dion-31580'),

    /*
    |--------------------------------------------------------------------------
    | Firestore Collection Names
    |--------------------------------------------------------------------------
    |
    | Collection names used in Firestore.
    |
    */
    'collections' => [
        'users' => 'users',
        'debts' => 'debts',
        'products' => 'products',
    ],

    /*
    |--------------------------------------------------------------------------
    | Use REST API instead of gRPC
    |--------------------------------------------------------------------------
    |
    | Set to true to force REST API usage (useful when gRPC extension is not available)
    |
    */
    'use_rest_api' => env('FIREBASE_USE_REST_API', true),

    /*
    |--------------------------------------------------------------------------
    | Firebase Cloud Messaging (FCM)
    |--------------------------------------------------------------------------
    |
    | FCM Server Key for sending push notifications.
    |
    */
    'fcm_server_key' => env('FCM_SERVER_KEY'),
];

