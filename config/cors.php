<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */
    'paths' => ['api/*', 'sanctum/csrf-cookie'], // Apply CORS to all API routes
    'allowed_methods' => ['*'], // Allow all HTTP methods
    'allowed_origins' => ['https://chilketiai.netlify.app', 'http://127.0.0.1:5173', 'https://ketiai.com'], // Ensure all origins have 'https://'
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'], // Allow all headers
    'exposed_headers' => ['Access-Control-Allow-Origin', 'Access-Control-Allow-Headers', 'Access-Control-Allow-Methods'],  // ✅ Expose necessary headers
    'max_age' => 0,
    'supports_credentials' => false,
];
