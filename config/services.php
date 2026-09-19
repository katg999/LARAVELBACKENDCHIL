<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Stripe, Mailgun, SparkPost and others. This file provides a sane
    | default location for this type of information, allowing packages
    | to have a conventional place to find your various credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'sendgrid' => [
    'api_key' => env('SENDGRID_API_KEY'),
    ],


    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'sparkpost' => [
        'secret' => env('SPARKPOST_SECRET'),
    ],

    'momo' => [
    'base_url' => env('MTN_MOMO_BASE_URL', 'https://sandbox.momodeveloper.mtn.com'),
    'primary_key' => env('MTN_MOMO_PRIMARY_KEY'),
    'secondary_key' => env('MTN_MOMO_SECONDARY_KEY'),
    'callback_url' => env('MTN_MOMO_CALLBACK_URL'),
    'api_user_id' => env('MTN_MOMO_API_USER_ID'),
    'env' => env('MTN_MOMO_ENV', 'sandbox'),
],

    'marzpay' => [
        'base_url' => env('MARZPAY_BASE_URL', 'https://wallet.wearemarz.com/api/v1'),
        'api_key' => env('MARZPAY_API_KEY'),
        'api_secret' => env('MARZPAY_API_SECRET'),
        'auth_header' => env('MARZPAY_AUTH_HEADER'),
        'webhook_secret' => env('MARZPAY_WEBHOOK_SECRET'),
    ],


    'stripe' => [
        'model' => App\User::class,
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook' => [
            'secret' => env('STRIPE_WEBHOOK_SECRET'),
            'tolerance' => env('STRIPE_WEBHOOK_TOLERANCE', 300),
        ],
    ],

    'africastalking' => [
        'username' => env('AFRICASTALKING_USERNAME'),
        'api_key' => env('AFRICASTALKING_API_KEY'),
        'sender_id' => env('AFRICASTALKING_SENDER_ID'),
        'url' => env('AFRICASTALKING_URL', 'https://api.africastalking.com/version1/messaging'),
    ],

    'payments' => [
        // Provider used by the appointment checkout. Add new gateways to AppServiceProvider.
        'default' => env('PAYMENT_GATEWAY', 'marzpay'),
    ],

    'jitsi' => [
        // Point this at a self-hosted Jitsi to get lobby/moderator controls.
        'base_url' => env('JITSI_BASE_URL', 'https://meet.jit.si'),
    ],

    'notifications' => [
        // sms, whatsapp (falls back to SMS) or both
        'channel' => env('NOTIFY_CHANNEL', 'sms'),
    ],

    'whatsapp' => [
        'token' => env('WHATSAPP_TOKEN'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'verify_token' => env('WHATSAPP_VERIFY_TOKEN'),
        'app_secret' => env('WHATSAPP_APP_SECRET'),
        'url' => env('WHATSAPP_API_URL', 'https://graph.facebook.com/v20.0'),
    ],

    'ussd' => [
        // Shared secret Africa's Talking is configured to send as ?token= on the callback URL.
        'secret' => env('USSD_SHARED_SECRET'),
    ],

];
