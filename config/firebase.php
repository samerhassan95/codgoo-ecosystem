<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Firebase Credentials
    |--------------------------------------------------------------------------
    |
    | Path to the Firebase service account JSON file.
    | You can store this file in a secure location, such as the `storage/app` directory.
    |
    */
    'credentials' => env('FIREBASE_CREDENTIALS', storage_path('firebase/codgoo-firebase.json')),

    /*
    |--------------------------------------------------------------------------
    | Firebase Real-Time Database URL
    |--------------------------------------------------------------------------
    |
    | The URL of your Firebase Real-Time Database.
    |
    */
    'database_url' => env('FIREBASE_DATABASE_URL', 'https://your-database-name.firebaseio.com'),

    /*
    |--------------------------------------------------------------------------
    | Firebase Cloud Messaging (FCM) Key
    |--------------------------------------------------------------------------
    |
    | The server key for Firebase Cloud Messaging (FCM).
    | This is optional and only required if you're using FCM directly.
    |
    */
    'fcm_key' => env('FIREBASE_FCM_KEY', 'your-fcm-server-key'),
];