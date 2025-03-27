<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    */
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    
    'allowed_methods' => ['*'],
    
    'allowed_origins' => [
        'https://chilketiai.netlify.app', 
        'http://127.0.0.1:5173', 
        'https://ketiai.com',
        'http://localhost:5173',
        'https://laravelbackendchil.onrender.com'
    ],
    
    'allowed_origins_patterns' => [],
    
    'allowed_headers' => ['*'],
    
    'exposed_headers' => [
        'Access-Control-Allow-Origin',
        'Access-Control-Allow-Headers', 
        'Access-Control-Allow-Methods'
    ],
    
    'max_age' => 0,
    
    'supports_credentials' => true
];