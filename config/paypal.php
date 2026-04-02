<?php

return [
    'mode' => env('PAYPAL_MODE', 'sandbox'),

    'client_id' => env('PAYPAL_MODE') === 'sandbox'
        ? env('PAYPAL_SANDBOX_CLIENT_ID')
        : env('PAYPAL_LIVE_CLIENT_ID'),

    'client_secret' => env('PAYPAL_MODE') === 'sandbox'
        ? env('PAYPAL_SANDBOX_CLIENT_SECRET')
        : env('PAYPAL_LIVE_CLIENT_SECRET'),

    'currency' => env('PAYPAL_CURRENCY', 'USD'),
];