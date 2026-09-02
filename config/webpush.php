<?php

return [
    /*
    |--------------------------------------------------------------------------
    | VAPID Credentials
    |--------------------------------------------------------------------------
    |
    | Voluntary Application Server Identification keys. The public key is handed
    | to the browser when it subscribes; the private key signs each push so the
    | push service can verify it came from this application.
    |
    | Generate a pair with:  php artisan push:generate-vapid-keys
    |
    | Push delivery is skipped entirely when these are unset, so the app runs
    | fine without them — the alert simply goes out over in-app and email only.
    |
    */
    'vapid' => [
        // Contact address the push service can reach if something goes wrong.
        'subject' => env('VAPID_SUBJECT', 'mailto:alerts@silvercare.local'),
        'public_key' => env('VAPID_PUBLIC_KEY'),
        'private_key' => env('VAPID_PRIVATE_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Delivery
    |--------------------------------------------------------------------------
    */

    // Only these severities are worth interrupting someone's day with a push.
    // Warnings and info still reach the caregiver in-app and on the dashboard.
    'push_severities' => ['critical', 'emergency'],

    // Seconds a push service should hold the message for a device that is
    // offline. Four hours: long enough for a phone in a pocket, short enough
    // that a caregiver is never surprised by a stale clinical alert.
    'ttl' => 14400,

    // 'very-low' | 'low' | 'normal' | 'high'. Clinical alerts are worth waking
    // the device for.
    'urgency' => 'high',

    // A subscription that the push service reports as gone (404/410) is deleted
    // rather than retried — the browser has revoked it.
    'prune_expired' => true,
];
